FROM python:3.12-slim

WORKDIR /app

COPY requirements.txt ./
RUN pip install --trusted-host pypi.org --trusted-host files.pythonhosted.org --no-cache-dir -r requirements.txt

RUN pip install --trusted-host pypi.org --trusted-host files.pythonhosted.org --no-cache-dir pandas

COPY . .

EXPOSE 8000

CMD ["/bin/sh", "-c", "if [ -f requirements.txt ]; then pip install --trusted-host pypi.org --trusted-host files.pythonhosted.org --no-cache-dir -r requirements.txt; fi; uvicorn main:app --host 0.0.0.0 --port 8000"]
