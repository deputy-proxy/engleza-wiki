import { execFileSync } from 'node:child_process';

export default function globalSetup() {
    execFileSync('php', ['artisan', 'db:seed', '--class=BrowserTestSeeder', '--force'], {
        env: { ...process.env, APP_ENV: 'testing' },
        stdio: 'inherit',
    });
}
