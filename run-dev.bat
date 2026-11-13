@echo off
title Running Sistem Keuangan CTC General Ledger
echo Cleaning configuration cache...
php artisan config:clear
php artisan route:clear

echo Starting Laravel Artisan Server & Vite...
start cmd /k "php artisan serve"
start cmd /k "npm run dev"

echo Done! Application running at http://127.0.0.1:8000

