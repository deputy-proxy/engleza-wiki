import { test, expect } from '@playwright/test';
import { readFileSync } from 'node:fs';

const uniqueEmail = () => `e2e-${Date.now()}-${Math.random().toString(16).slice(2)}@example.test`;

test('public visitors can discover resources and open a published lesson', async ({ page }) => {
    await page.goto('/');
    await expect(page).toHaveTitle(/engleza/i);
    await page.getByRole('link', { name: /resources|resurse/i }).first().click();
    await expect(page).toHaveURL(/resources/);
    await expect(page.locator('main')).toBeVisible();
    await page.goto('/resources/understanding-the-present-simple');
    await expect(page.getByRole('heading', { name: /present simple/i }).first()).toBeVisible();
});

test('guest can discover assessment content and is prompted to register or log in for protected learning', async ({ page }) => {
    await page.goto('/tests');
    await expect(page.getByTestId('tests-grid')).toBeVisible();
    await page.locator('[data-testid^="test-card-"]').first().click();
    await expect(page.getByTestId('test-title')).toBeVisible();
    await page.getByTestId('start-test-btn').click();
    await expect(page).toHaveURL(/\/take\/\d+$/);
    await expect(page.getByTestId('question-card')).toBeVisible();
    await page.waitForFunction(() => Boolean(window.Livewire && typeof window.Livewire.all === 'function' && window.Livewire.all().length > 0));
    const firstOption = page.locator('[data-testid^="option-"]').first();
    await firstOption.click();
    await expect(firstOption).toHaveAttribute('aria-pressed', 'true');
    await page.goto('/dashboard');
    await expect(page).toHaveURL(/login/);
});

test('registration validates password confirmation', async ({ page }) => {
    await page.goto('/register');
    await page.getByTestId('register-name').fill('Browser Test Learner');
    await page.getByTestId('register-email').fill(uniqueEmail());
    await page.getByTestId('register-password').fill('StrongPassword123!');
    await page.getByTestId('register-password-confirm').fill('DifferentPassword123!');
    await page.getByTestId('register-submit').click();
    await expect(page.getByText(/confirmarea câmpului parolă nu corespunde/i)).toBeVisible();
});

test('new learner registers, receives a verification email, and verifies the account', async ({ page }) => {
    const email = uniqueEmail();
    await page.goto('/register');
    await page.getByTestId('register-name').fill('Browser Test Learner');
    await page.getByTestId('register-email').fill(email);
    await page.getByTestId('register-password').fill('StrongPassword123!');
    await page.getByTestId('register-password-confirm').fill('StrongPassword123!');
    await page.getByTestId('register-submit').click();
    await expect(page).toHaveURL(/email\/verify/);
    await expect(page.locator('main')).toContainText(/verify|verification|confirm/i);

    const mailLog = readFileSync('storage/logs/laravel.log', 'utf8');
    const recipientPosition = mailLog.lastIndexOf(`To: ${email}`);
    expect(recipientPosition).toBeGreaterThanOrEqual(0);
    const message = mailLog.slice(recipientPosition, recipientPosition + 10_000);
    const verificationLink = message.match(/Confirmă adresa de e-mail:\s*(https?:\/\/[^\s]+)/);
    expect(verificationLink).not.toBeNull();
    await page.goto(verificationLink[1]);
    await expect(page).toHaveURL(/dashboard/);
    await page.goto('/email/verify');
    await expect(page).toHaveURL(/dashboard/);
});

test('learner can log in and access the dashboard and learning area', async ({ page }) => {
    await page.goto('/login');
    await page.getByTestId('login-email').fill('learner@engleza.wiki');
    await page.getByTestId('login-password').fill('password');
    await page.getByTestId('login-submit').click();
    await expect(page).toHaveURL(/dashboard/);
    await page.goto('/my-learning');
    await expect(page.locator('main')).toBeVisible();
});

test('teacher can access class management while a learner cannot', async ({ browser }) => {
    const teacher = await browser.newPage();
    await teacher.goto('http://127.0.0.1:8000/login');
    await teacher.getByTestId('login-email').fill('teacher@engleza.wiki');
    await teacher.getByTestId('login-password').fill('password');
    await teacher.getByTestId('login-submit').click();
    await expect(teacher).toHaveURL(/dashboard/);
    await teacher.goto('http://127.0.0.1:8000/teaching');
    await expect(teacher.locator('main')).toBeVisible();

    const learner = await browser.newPage();
    await learner.goto('http://127.0.0.1:8000/login');
    await learner.getByTestId('login-email').fill('learner@engleza.wiki');
    await learner.getByTestId('login-password').fill('password');
    await learner.getByTestId('login-submit').click();
    const denied = await learner.goto('http://127.0.0.1:8000/teaching');
    expect([302, 403]).toContain(denied.status());
    await teacher.close();
    await learner.close();
});

test('admin can open content management and learners are denied', async ({ browser }) => {
    const admin = await browser.newPage();
    await admin.goto('http://127.0.0.1:8000/login');
    await admin.getByTestId('login-email').fill('admin@engleza.wiki');
    await admin.getByTestId('login-password').fill('password');
    await admin.getByTestId('login-submit').click();
    await admin.goto('http://127.0.0.1:8000/admin');
    await expect(admin).toHaveURL(/admin/);
    await expect(admin.locator('body')).not.toContainText(/403|unauthorized/i);

    const learner = await browser.newPage();
    await learner.goto('http://127.0.0.1:8000/login');
    await learner.getByTestId('login-email').fill('learner@engleza.wiki');
    await learner.getByTestId('login-password').fill('password');
    await learner.getByTestId('login-submit').click();
    const denied = await learner.goto('http://127.0.0.1:8000/admin');
    expect([302, 403]).toContain(denied.status());
    await admin.close();
    await learner.close();
});

test('learner dashboard remains usable on a mobile viewport', async ({ page }) => {
    await page.setViewportSize({ width: 390, height: 844 });
    await page.goto('/login');
    await page.getByTestId('login-email').fill('learner@engleza.wiki');
    await page.getByTestId('login-password').fill('password');
    await page.getByTestId('login-submit').click();
    await expect(page).toHaveURL(/dashboard/);
    await expect(page.locator('body')).toBeVisible();
    const overflow = await page.evaluate(() => document.documentElement.scrollWidth > document.documentElement.clientWidth);
    expect(overflow).toBe(false);
});

test('listening and reading exercises render their learning activities', async ({ page }) => {
    await page.goto('/resources/a2-listening-at-the-restaurant');
    await expect(page.getByTestId('resource-title')).toBeVisible();
    await expect(page.getByTestId('listening-player')).toBeVisible();
    await expect(page.getByTestId('listening-no-media')).toBeVisible();
    await expect(page.locator('text=Where are the speakers?')).toBeVisible();
    await page.locator('[data-testid^="ex-option-"]').first().click();
    await page.getByTestId('exercise-submit').click();
    await expect(page.getByTestId('exercise-result')).toBeVisible();

    await page.goto('/resources/a2-reading-a-day-in-london');
    await expect(page.getByTestId('resource-title')).toBeVisible();
    await expect(page.getByTestId('reading-passage')).toContainText('London is one of the most exciting cities');
    await expect(page.locator('text=Which is a famous museum mentioned in the text?')).toBeVisible();
    await page.locator('[data-testid^="ex-option-"]').first().click();
    await page.getByTestId('exercise-submit').click();
    await expect(page.getByTestId('exercise-result')).toBeVisible();
});

test('learner submits writing, teacher publishes feedback, and learner can read it', async ({ browser }) => {
    test.setTimeout(60_000);
    const learner = await browser.newPage();
    await learner.goto('http://127.0.0.1:8000/login');
    await learner.getByTestId('login-email').fill('learner@engleza.wiki');
    await learner.getByTestId('login-password').fill('password');
    await learner.getByTestId('login-submit').click();
    await expect(learner).toHaveURL(/dashboard/);
    await learner.goto('http://127.0.0.1:8000/classes/E2E26A');
    await learner.locator('textarea[name="content"]').fill('I usually start my day with breakfast. Then I travel to work and meet my colleagues.');
    await learner.locator('button[name="status"][value="submitted"]').click();

    const teacher = await browser.newPage();
    await teacher.goto('http://127.0.0.1:8000/login');
    await teacher.getByTestId('login-email').fill('teacher@engleza.wiki');
    await teacher.getByTestId('login-password').fill('password');
    await teacher.getByTestId('login-submit').click();
    await teacher.goto('http://127.0.0.1:8000/classes/E2E26A/writing');
    const submissionLink = teacher.locator('[data-testid^="teacher-writing-"]').first();
    await expect(submissionLink).toBeVisible();
    await submissionLink.click();
    await teacher.locator('form button').filter({ hasText: /start review|începe evaluarea/i }).click();

    const feedbackForm = teacher.locator('form[action$="/feedback"]');
    for (const criterion of ['task_achievement', 'organization', 'vocabulary', 'grammar', 'mechanics']) {
        await feedbackForm.locator(`select[name="rubric_scores[${criterion}]"]`).selectOption('3');
    }
    await feedbackForm.locator('textarea[name="overall_feedback"]').fill('Clear response. Add more supporting detail to the second point.');
    await feedbackForm.locator('button').last().click();
    await expect(teacher.locator('body')).toContainText('Clear response. Add more supporting detail');

    const match = teacher.url().match(/\/writing\/(\d+)$/);
    expect(match).not.toBeNull();
    await learner.goto(`http://127.0.0.1:8000/my-writing/${match[1]}`, { waitUntil: 'domcontentloaded' });
    await expect(learner.locator('main')).toContainText('Clear response. Add more supporting detail');
    await teacher.close();
    await learner.close();
});

test('state-changing requests without a CSRF token are rejected', async ({ page }) => {
    const response = await page.request.post('/login', {
        form: { email: 'learner@engleza.wiki', password: 'password' },
    });
    expect(response.status()).toBe(419);
});

test('learner enrolls in a learning path and progresses through sequential lessons', async ({ page }) => {
    await page.goto('/login');
    await page.getByTestId('login-email').fill('learner@engleza.wiki');
    await page.getByTestId('login-password').fill('password');
    await page.getByTestId('login-submit').click();
    await page.goto('/learning/e2e-browser-learning-path');
    await page.locator('form[action$="/enroll"] button').click();
    await expect(page.locator('body')).toContainText(/enrolled|înscris/i);

    await page.goto('/learning/lessons/e2e-browser-lesson-1');
    await expect(page.getByRole('heading', { name: 'Browser E2E Lesson 1' })).toBeVisible();
    await page.locator('form[action*="/activities/"] button').click();
    await page.locator('form[action*="/lessons/"] button').click();
    await expect(page.locator('body')).toContainText(/lesson.*completed|lecția a fost finalizată/i);

    await page.goto('/learning/lessons/e2e-browser-lesson-2');
    await expect(page.getByRole('heading', { name: 'Browser E2E Lesson 2' })).toBeVisible();
    await page.goto('/my-learning');
    await expect(page.locator('main')).toContainText('Browser E2E Learning Path');
});
