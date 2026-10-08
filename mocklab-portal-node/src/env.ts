// Settings from the optional .env file (copy .env.dist to .env).
import { existsSync } from 'node:fs';

if (existsSync('.env')) {
  process.loadEnvFile('.env'); // built into Node, fills process.env
}

// Returns the value of $key, or $fallback when the key is missing.
export function env(key: string, fallback?: string): string | undefined {
  return process.env[key] ?? fallback;
}
