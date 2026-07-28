# Telegram Aadhaar->PAN Bot

This repository contains a PHP 8.2 Telegram bot that accepts a 12-digit Aadhaar number and returns the corresponding PAN number by calling https://www.apicentre.in/api/aadhaar_to_pan.

Features
- PHP 8.2
- Docker + docker-compose support
- Koyeb deployment guide
- Telegram webhook handling
- Reads BOT_TOKEN and API_KEY from environment variables (or .env)
- Verhoeff validation for Aadhaar
- cURL call to external API
- Error handling and logging (Monolog)

Files
- Dockerfile
- docker-compose.yml
- bot.php
- webhook.php
- config.php
- functions.php
- index.php
- composer.json
- README.md
- .env.example
- .gitignore

Quick start (local)
1. Copy .env.example to .env and fill BOT_TOKEN and API_KEY.
2. Build and run with docker-compose:

   docker-compose up --build

3. The app will be available on http://localhost:8080/

Set Telegram webhook (example):

Replace <YOUR_BASE_URL> with publicly accessible URL (use ngrok or deploy to Koyeb). Then run:

curl -X POST "https://api.telegram.org/bot$BOT_TOKEN/setWebhook" -d "url=https://<YOUR_BASE_URL>/webhook.php"

Koyeb deployment
1. Push this repo to GitHub.
2. On Koyeb, create a new app and connect to your GitHub repo, or deploy using Dockerfile.
3. Set environment variables BOT_TOKEN and API_KEY in Koyeb.
4. Expose the webhook endpoint at /webhook.php and set Telegram webhook to https://<your-koyeb-domain>/webhook.php

Security notes
- Keep BOT_TOKEN and API_KEY secret. Do NOT commit a real .env to the repository.

