import { defineConfig } from "vite";
import react from "@vitejs/plugin-react";
import { VitePWA } from "vite-plugin-pwa";

export default defineConfig({
  plugins: [
    react(),
    VitePWA({
      registerType: "autoUpdate",
      includeAssets: ["registros.ico", "apple-touch-icon.png", "mask-icon.svg"],
      manifest: {
        name: "Registros de Tareas",
        short_name: "RegTareas",
        description: "App de registros de tareas",
        theme_color: "#2563eb",
        background_color: "#ffffff",
        display: "standalone",
        start_url: "/",
        icons: [
          { src: "/registros-192x192.png", sizes: "192x192", type: "image/png" },
          { src: "/registros-512x512.png", sizes: "512x512", type: "image/png" },
          { src: "/registros-512x512.png", sizes: "512x512", type: "image/png", purpose: "any maskable" }
        ]
      }
    })
  ]
});