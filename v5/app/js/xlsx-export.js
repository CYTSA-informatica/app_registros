/**
 * Export utility for tables to Excel (XLSX)
 * Uses SheetJS (xlsx) library
 */

const XlsxExport = (() => {
  const getDateStamp = () => {
    const now = new Date();
    const day = String(now.getDate()).padStart(2, '0');
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const year = String(now.getFullYear());

    return `${day}-${month}-${year}`;
  };

  /**
   * Export visible table rows to Excel (single sheet with current filters and visible columns)
   * @param {string} buttonSelector - CSS selector for export button
   * @param {string} tbodySelector - CSS selector for tbody
   * @param {string} filename - Filename for the Excel (without extension)
   * @param {Object} options - Additional options
   * @param {string} options.tabla - Table name for backend: 'registers', 'clients', 'providers', 'users'
   */
  const exportVisibleToExcel = async (buttonSelector, tbodySelector, filename, options = {}) => {
    const button = document.querySelector(buttonSelector);
    if (!button) {
      console.error(`Export button not found: ${buttonSelector}`);
      return;
    }

    const tabla = options.tabla || 'registers';

    button.addEventListener('click', async () => {
      try {
        const tbody = document.querySelector(tbodySelector);
        if (!tbody) {
          console.error(`Tbody not found: ${tbodySelector}`);
          alert('No se encontró la tabla para exportar');
          return;
        }

        // Collect visible row IDs from the table
        const visibleRows = [];
        tbody.querySelectorAll('tr').forEach((tr) => {
          if (tr.style.display === 'none') return;
          // Get ID from data-id attribute
          const idAttr = tr.getAttribute('data-id');
          if (idAttr) {
            const parsedId = parseInt(idAttr, 10);
            if (Number.isFinite(parsedId) && parsedId > 0) {
              visibleRows.push(parsedId);
            }
          }
        });

        console.log('IDs a exportar:', visibleRows, 'tabla:', tabla);

        if (visibleRows.length === 0) {
          console.warn('No visible rows found');
          alert('No hay registros para exportar (todos están filtrados)');
          return;
        }

        // Fetch all data from backend with IDs
        const response = await fetch('index.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: `action=export_by_ids&tabla=${encodeURIComponent(tabla)}&ids=${encodeURIComponent(JSON.stringify(visibleRows))}`,
        });

        if (!response.ok) {
          throw new Error('Error al obtener datos: ' + response.status);
        }

        const result = await response.json();
        if (result.error) {
          throw new Error(result.error);
        }

        const rows = result.rows || [];
        if (rows.length === 0) {
          alert('No hay datos para exportar');
          return;
        }

        // Get all column names from first row
        const firstRow = rows[0];
        const headers = Object.keys(firstRow);

        // Build Excel data
        const wsData = [headers];
        rows.forEach((row) => {
          const rowData = headers.map((col) => {
            const val = row[col];
            return val !== null && val !== undefined ? String(val) : '';
          });
          wsData.push(rowData);
        });

        // Build workbook with single sheet
        const wb = XLSX.utils.book_new();
        const ws = XLSX.utils.aoa_to_sheet(wsData);
        XLSX.utils.book_append_sheet(wb, ws, filename || 'Registros');

        // Generate and download
        const timestamp = getDateStamp();
        const fullFilename = `${filename}_${timestamp}.xlsx`;
        XLSX.writeFile(wb, fullFilename);

        console.log(`✓ Exportado a ${fullFilename} (${rows.length} registros)`);
        alert('✓ Exportación completada: ' + fullFilename);
      } catch (error) {
        console.error('Error during export:', error);
        alert('Error al exportar: ' + error.message);
      }
    });
  };

  /**
   * Download XLSX from server endpoint (legacy - exports all data)
   */
  const exportFullToExcel = async (filename) => {
    try {
      const response = await fetch('index.php?export=excel');
      if (!response.ok) {
        throw new Error('Error al obtener datos: ' + response.status);
      }

      const data = await response.json();

      // Build workbook with multiple sheets
      const wb = XLSX.utils.book_new();

      const addSheet = (name, rows) => {
        if (rows && rows.length > 0) {
          const ws = XLSX.utils.aoa_to_sheet(rows);
          XLSX.utils.book_append_sheet(wb, ws, name);
        }
      };

      addSheet('Registros', data.sheet1);
      addSheet('Clientes', data.sheet2);
      addSheet('Proveedores', data.sheet3);
      addSheet('Cliente_Registro', data.sheet4);
      addSheet('Proveedor_Registro', data.sheet5);

      // Generate and download
      const timestamp = getDateStamp();
      const fullFilename = `${filename}_${timestamp}.xlsx`;
      XLSX.writeFile(wb, fullFilename);

      console.log(`✓ Exportado a ${fullFilename}`);
      alert('✓ Exportación completada: ' + fullFilename);
    } catch (error) {
      console.error('Error during export:', error);
      alert('Error al exportar: ' + error.message);
    }
  };

  /**
   * Export visible table rows to CSV (legacy function)
   * @param {string} tableSelector - CSS selector for the table
   * @param {string} tbodySelector - CSS selector for tbody
   * @param {string} filename - Filename for the CSV (without extension)
   * @param {Object} options - Additional options
   * @param {boolean} options.includeHiddenColumns - Include columns with d-none-mobile class (default: false)
   */
  const exportToCSV = (tableSelector, tbodySelector, filename, options = {}) => {
    const table = document.querySelector(tableSelector);
    const tbody = document.querySelector(tbodySelector);

    if (!table || !tbody) {
      console.error(`Table or tbody not found: ${tableSelector}, ${tbodySelector}`);
      alert('No se encontró la tabla para exportar');
      return;
    }

    // Extract headers with their indices
    const headerElements = Array.from(table.querySelectorAll('thead th'));
    const headers = [];
    const visibleHeaderIndices = [];

    headerElements.forEach((th, index) => {
      const isActionCell = th.classList.contains('action-cell');
      const isHidden = th.classList.contains('d-none-mobile');

      if (!isActionCell && (options.includeHiddenColumns || !isHidden)) {
        headers.push(th.textContent.trim());
        visibleHeaderIndices.push(index);
      }
    });

    if (headers.length === 0) {
      console.error('No headers found');
      alert('No se encontraron columnas para exportar');
      return;
    }

    // Extract visible rows
    const rows = [];
    tbody.querySelectorAll('tr').forEach((tr) => {
      if (tr.style.display === 'none') return;

      const row = [];
      const cells = tr.querySelectorAll('td');

      visibleHeaderIndices.forEach((headerIndex) => {
        const td = cells[headerIndex];
        if (td) {
          row.push(td.textContent.trim());
        }
      });

      if (row.length > 0) {
        rows.push(row);
      }
    });

    if (rows.length === 0) {
      console.warn('No visible rows found');
      alert('No hay registros para exportar (todos están filtrados)');
      return;
    }

    // Build CSV content with semicolon separator
    const escapeCSVField = (field) => {
      const value = String(field).trim();
      if (value.includes(';') || value.includes('\n') || value.includes('"')) {
        return `"${value.replace(/"/g, '""')}"`;
      }
      return value;
    };

    let csv = '\uFEFF';
    csv += headers.map((h) => escapeCSVField(h)).join(';') + '\n';
    rows.forEach((row) => {
      csv += row.map((cell) => escapeCSVField(cell)).join(';') + '\n';
    });

    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const timestamp = getDateStamp();
    const fullFilename = `${filename}_${timestamp}.csv`;

    link.setAttribute('href', URL.createObjectURL(blob));
    link.setAttribute('download', fullFilename);
    link.style.visibility = 'hidden';

    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);

    URL.revokeObjectURL(link.href);

    console.log(`✓ Exportados ${rows.length} registros a ${fullFilename}`);
  };

  /**
   * Attach export button to trigger full Excel export
   * @param {string} buttonSelector - CSS selector for export button
   * @param {string} filename - Filename for the Excel (without extension)
   */
  const attachExportButton = (buttonSelector, filename) => {
    const button = document.querySelector(buttonSelector);
    if (!button) {
      console.error(`Export button not found: ${buttonSelector}`);
      return;
    }

    button.addEventListener('click', async () => {
      await exportFullToExcel(filename);
    });

    console.log(`✓ Export button attached: ${buttonSelector}`);
  };

  /**
   * Attach export button to trigger visible-only Excel export (filters applied + all columns from backend)
   * @param {string} buttonSelector - CSS selector for export button
   * @param {string} tbodySelector - CSS selector for tbody
   * @param {string} filename - Filename for the Excel (without extension)
   * @param {Object} options - Additional options
   * @param {string} options.tabla - Table name: 'registers', 'clients', 'providers', 'users'
   */
  const attachExportVisibleButton = (buttonSelector, tbodySelector, filename, options = {}) => {
    exportVisibleToExcel(buttonSelector, tbodySelector, filename, options);
  };

  // Public API
  return {
    exportToCSV,
    exportFullToExcel,
    attachExportButton,
    attachExportVisibleButton,
  };
})();
