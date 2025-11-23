# React + Vite

This template provides a minimal setup to get React working in Vite with HMR and some ESLint rules.

Currently, two official plugins are available:

- [@vitejs/plugin-react](https://github.com/vitejs/vite-plugin-react/blob/main/packages/plugin-react) uses [Babel](https://babeljs.io/) (or [oxc](https://oxc.rs) when used in [rolldown-vite](https://vite.dev/guide/rolldown)) for Fast Refresh
- [@vitejs/plugin-react-swc](https://github.com/vitejs/vite-plugin-react/blob/main/packages/plugin-react-swc) uses [SWC](https://swc.rs/) for Fast Refresh

## React Compiler

The React Compiler is not enabled on this template because of its impact on dev & build performances. To add it, see [this documentation](https://react.dev/learn/react-compiler/installation).

## Expanding the ESLint configuration

If you are developing a production application, we recommend using TypeScript with type-aware lint rules enabled. Check out the [TS template](https://github.com/vitejs/vite/tree/main/packages/create-vite/template-react-ts) for information on how to integrate TypeScript and [`typescript-eslint`](https://typescript-eslint.io) in your project.

## Email configuration (development and production)

This project ships with a flexible email sender. You can choose between SMTP (dev or some hosts) and an HTTP API provider (recommended for production/sharerd hosts).

Files:
- `backend/config/.env` — your environment values (do NOT commit this file)
- `backend/config/.env.example` — example Gmail-focused env (copy to `.env` and fill with your Gmail app password)
- `backend/config/examples/` — archived provider examples (Mailtrap/SendGrid) if you need them later

Drivers:
- `MAIL_DRIVER=smtp` — uses PHPMailer and the SMTP server in `EMAIL_HOST` (good for Gmail or other SMTP providers)

Local development
- Copy `backend/config/.env.example` to `backend/config/.env` and fill it with your Gmail app password or your preferred SMTP provider's credentials.
- Restart Apache/XAMPP if necessary so PHP reads the new env.
- Use the moved debug scripts under `backend/tests/` to test sending and environment settings. Example using PHP built-in server:

```powershell
php -S localhost:8000 -t backend
# then visit http://localhost:8000/tests/test_email.php?to=you@example.com&debug=1
```

Notes
- Many free shared hosts block outbound SMTP ports. If your host blocks SMTP in production, use a hosted email API provider (Mailgun, Postmark, etc.) which operate over HTTPS (port 443) and are typically allowed.

## Google OAuth (optional)

This project includes a basic Google OAuth callback handler at `backend/public/google_callback.php` and supports creating/linking users using Google account information.

Environment variables to add to `backend/config/.env` when you enable Google OAuth:

- `GOOGLE_CLIENT_ID` — your Google OAuth client ID
- `GOOGLE_CLIENT_SECRET` — your Google OAuth client secret
- `GOOGLE_REDIRECT_URI` — the redirect URI you register in Google Console (e.g. `https://yourdomain.com/personalized-fitness-workout/backend/public/google_callback.php`)

Frontend / API base (Vite)
 - During development we recommend setting a Vite env variable so the frontend knows which backend URL to call.
 - Create a `.env.local` at the project root with:

```
VITE_API_BASE=http://localhost/personalized-fitness-workout/backend/public
```

 - In production set `VITE_API_BASE` to your Hostinger API URL (e.g. `https://api.yourdomain.com` or the full path where you upload the `public` API).

Notes:
- The callback will exchange the authorization code for user info and then:
	- login existing Google-linked users,
	- link Google accounts to existing users with the same email, or
	- create a new user (with `Password = NULL`, `Is_Verified = 1`, `Login_Method = 'google'`).
- Make sure your `user` table has `Google_ID` and `Login_Method` columns (and that `Password` allows NULL) — a sample migration was included earlier in the project conversation.
 
## AI Provider Integration

This project can proxy requests to OpenAI or Gemini from the backend. The backend endpoints are implemented in `backend/app/services` and exposed via the API route `?route=ai&action=chat`.

Environment variables (set them in `backend/config/env.php` for local development or use a secure `.env`/system env for production):

- `OPENAI_API_KEY` - Your OpenAI API key (server-side only)
- `OPENAI_MODEL` - Default OpenAI model to use (optional)
- `GEMINI_API_URL` - Full Gemini endpoint URL (if using Gemini)
- `GEMINI_API_KEY` - Gemini/Google API key or OAuth token

Preferred free-tier setup for a month (recommended flow)

- Primary: Gemini 2.0 Flash via Google Cloud trial credits (use as first attempt). Example endpoint:
	`https://gemini.googleapis.com/v1/models/gemini-2.0-flash:generate`
- Fallback: OpenAI GPT-4o mini (use OpenAI free credits if available)

Flow implemented in this project:
1. Try Gemini 2.0 Flash (2 attempts) and attempt to extract a JSON object from the model output.
2. If Gemini fails to provide valid JSON after 2 attempts, try OpenAI GPT-4o mini (2 attempts).
3. If OpenAI still can't produce valid JSON after 2 attempts, the backend returns an error and you can prompt the user to refine input.

Defaults tuned for free-tier usage in `AIService`:
- attempts per provider: 2
- max_tokens per generation: 300
- temperature: 0.2


Example curl (PowerShell) to generate a prompt via the backend proxy:

```powershell
curl -X POST "http://localhost/personalized-fitness-workout/backend/public/index.php?route=ai&action=chat" `
  -H "Content-Type: application/json" `
  -d '{"provider":"openai","prompt":"Write a 3-exercise beginner full-body workout","options":{"max_tokens":300}}'
```

You can also run a quick PHP test script included in the repo:

```powershell
php backend/scripts/test_ai.php
```

Notes:
- Keep API keys server-side. Do not expose them in frontend code or commit them to git.
- The Gemini service expects `GEMINI_API_URL` to point to the exact generation endpoint your Google Cloud project provides; payload format may vary depending on your account. If needed, adapt `backend/app/services/GeminiService.php` to match the expected JSON structure.
- For production, consider adding retries, logging, and per-user rate limits.

