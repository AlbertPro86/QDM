#!/bin/bash
# =============================================
# deploy.sh — QUANTUN Digital
# Ejecutar en el servidor de Hostinger via SSH
# =============================================

set -e

REPO_DIR="$HOME/quantun-repo"
WEB_DIR="$HOME/public_html"
CRM_DIR="$HOME/public_html/crm"

echo ">>> Actualizando repo..."
cd "$REPO_DIR"
git pull origin master

echo ">>> Desplegando sitio web en public_html/..."
rsync -av --delete \
  --exclude='.git' \
  --exclude='crm/' \
  "$REPO_DIR/quantun-web/" "$WEB_DIR/"

echo ">>> Desplegando CRM en public_html/crm/..."
rsync -av --delete \
  --exclude='.git' \
  --exclude='quantun-web/' \
  --exclude='backups/' \
  --exclude='.env' \
  --exclude='uploads/' \
  --exclude='deploy.sh' \
  "$REPO_DIR/" "$CRM_DIR/"

echo ">>> Creando carpetas de uploads si no existen..."
mkdir -p "$CRM_DIR/uploads/facturas"
mkdir -p "$CRM_DIR/uploads/transacciones"
mkdir -p "$CRM_DIR/uploads/clientes"

echo ""
echo "OK - Deploy completado."
echo "Recuerda: el archivo .env debe estar en $CRM_DIR/.env"
