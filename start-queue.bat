@echo off
cd /d E:\downloads\career\studai-career
php artisan queue:work --tries=3 --timeout=300
