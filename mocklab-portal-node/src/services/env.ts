// Settings: a real environment variable wins (e.g. set by Docker), otherwise the optional
// .env file in the project root is used (copy .env.dist to .env).
import { existsSync } from 'node:fs';
import { join } from 'node:path';

const envFile = join(import.meta.dirname, '../../.env');
if (existsSync(envFile)) {
  process.loadEnvFile(envFile); // built into Node, does not override variables that are already set
}

// Returns the value of the setting, or the fallback when it is missing.
export function env(key: string, fallback?: string): string | undefined {
  return process.env[key] ?? fallback;
}
