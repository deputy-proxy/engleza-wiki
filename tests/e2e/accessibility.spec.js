import { test, expect } from '@playwright/test';
import AxeBuilder from '@axe-core/playwright';

const expectNoSeriousAxeViolations = async (page, route) => {
    const results = await new AxeBuilder({ page })
        .withTags(['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa', 'wcag22aa'])
        .analyze();
    const serious = results.violations.filter(violation => ['serious', 'critical'].includes(violation.impact));
    expect(serious, `${route}: ${JSON.stringify(serious.map(({ id, impact, nodes }) => ({ id, impact, nodes: nodes.map(node => node.target) })))}`).toEqual([]);
};

 test('keyboard users can skip the shared navigation and reach main content', async ({ page }) => {
    await page.goto('/');
    await page.keyboard.press('Tab');
    await expect(page.locator('a.skip-link')).toBeFocused();
    await page.keyboard.press('Enter');
    await expect(page.locator('main#main-content')).toBeFocused();
});

test('mobile primary navigation exposes its state and all primary destinations', async ({ page }) => {
    await page.setViewportSize({ width: 390, height: 844 });
    await page.goto('/');
    const toggle = page.locator('[aria-controls="mobile-primary-navigation"]');
    await expect(toggle).toHaveAttribute('aria-expanded', 'false');
    await toggle.focus();
    await page.keyboard.press('Enter');
    await expect(toggle).toHaveAttribute('aria-expanded', 'true');
    const navigation = page.locator('#mobile-primary-navigation');
    await expect(navigation).toBeVisible();
    await expectNoSeriousAxeViolations(page, '/ mobile navigation open');
    await expect(navigation.locator('a[href$="/resources"]')).toBeVisible();
    await navigation.locator('a[href$="/resources"]').click();
    await expect(page).toHaveURL(/resources/);
});

test('authentication controls have associated labels and errors are announced', async ({ page }) => {
    await page.goto('/login');
    await expect(page.locator('#login-email')).toHaveAttribute('autocomplete', 'username');
    await expect(page.locator('#login-password')).toHaveAttribute('autocomplete', 'current-password');
    await page.locator('#login-email').fill('wrong@example.test');
    await page.locator('#login-password').fill('wrong-password');
    await page.locator('[data-testid="login-submit"]').click();
    await expect(page.getByRole('alert')).toBeVisible();
    await expect(page.locator('#login-email')).toHaveAttribute('aria-invalid', 'true');

    await page.goto('/register');
    await page.locator('#register-name').fill('Accessibility Test');
    await page.locator('#register-email').fill(`a11y-${Date.now()}@example.test`);
    await page.locator('#register-password').fill('StrongPassword123!');
    await page.locator('#register-password_confirmation').fill('DifferentPassword123!');
    await page.locator('[data-testid="register-submit"]').click();
    await expect(page.locator('#register-password')).toHaveAttribute('aria-invalid', 'true');
    await expect(page.locator('#register-password')).toHaveAttribute('aria-describedby', 'register-password-error');
    await expect(page.locator('#register-password-error')).toHaveAttribute('role', 'alert');
});

test('reduced-motion preference disables pointer-driven homepage tilt', async ({ page }) => {
    await page.emulateMedia({ reducedMotion: 'reduce' });
    await page.goto('/');
    const card = page.locator('#interactive-3d-card');
    const bounds = await card.boundingBox();
    await page.mouse.move(bounds.x + bounds.width - 10, bounds.y + 10);
    expect(await card.evaluate(element => element.style.transform)).toBe('');
});

test('search and resource filters expose accessible names', async ({ page }) => {
    await page.goto('/');
    await expect(page.locator('#home-search-input')).toBeVisible();
    await page.goto('/resources');
    await expect(page.locator('#resource-filter-type')).toBeVisible();
    await expect(page.locator('#resource-filter-level')).toBeVisible();
    await expect(page.locator('#resource-filter-skill')).toBeVisible();
});


test('representative public learner pages have no serious or critical WCAG 2.2 AA violations', async ({ page }) => {
    for (const route of ['/', '/login', '/register', '/resources', '/resources/a2-listening-at-the-restaurant', '/resources/a2-reading-a-day-in-london', '/resources/a2-writing-describe-your-daily-routine']) {
        await page.goto(route);
        await expectNoSeriousAxeViolations(page, route);
    }
});


test('flashcards, assessments, dashboard and learning paths expose accessible states', async ({ page }) => {
    test.setTimeout(60_000);
    await page.goto('/resources/everyday-english-vocabulary-flashcards');
    const flashcard = page.getByTestId('flashcard-toggle');
    await expect(flashcard).toHaveAttribute('aria-pressed', 'false');
    await flashcard.focus();
    await page.keyboard.press('Enter');
    await expect(flashcard).toHaveAttribute('aria-pressed', 'true');
    await expect(flashcard).toBeFocused();
    await expectNoSeriousAxeViolations(page, '/resources/everyday-english-vocabulary-flashcards');

    await page.goto('/tests');
    await page.locator('[data-testid^="test-card-"]').first().click();
    await page.getByTestId('start-test-btn').click();
    await expect(page.getByTestId('question-card')).toBeVisible();
    const answer = page.locator('[data-testid^="option-"]').first();
    await answer.focus();
    await page.keyboard.press('Enter');
    await expect(answer).toHaveAttribute('aria-pressed', 'true');
    await expect(answer).toBeFocused();
    const firstQuestion = await page.getByTestId('question-text').textContent();
    const nextQuestion = page.getByTestId('next-btn');
    await nextQuestion.click();
    await expect(page.getByTestId('question-text')).not.toHaveText(firstQuestion);
    await expect(nextQuestion).toBeFocused();
    await expectNoSeriousAxeViolations(page, '/take assessment');

    await page.goto('/login');
    await page.locator('#login-email').fill('learner@engleza.wiki');
    await page.locator('#login-password').fill('password');
    await page.locator('[data-testid="login-submit"]').click();
    await expect(page).toHaveURL(/dashboard/);
    await page.goto('/resources/a2-writing-describe-your-daily-routine');
    const writingResponse = page.locator('#writing-response');
    await writingResponse.fill('');
    await page.locator('[data-testid="writing-submit"]').click();
    await expect(writingResponse).toHaveAttribute('aria-invalid', 'true');
    await expect(page.locator('#writing-response-error')).toBeVisible();
    await expect(writingResponse).toHaveAttribute('aria-describedby', /writing-response-error/);
    await expectNoSeriousAxeViolations(page, '/resources/a2-writing-describe-your-daily-routine validation');

    await page.goto('/dashboard');
    await expectNoSeriousAxeViolations(page, '/dashboard');
    const resultLink = page.locator('a[href*="/tests/vocabulary-test-everyday-english/result/"]').first();
    await expect(resultLink).toBeVisible();
    await resultLink.click();
    await expect(page).toHaveURL(/\/tests\/vocabulary-test-everyday-english\/result\/\d+$/);
    await expectNoSeriousAxeViolations(page, '/tests/vocabulary-test-everyday-english/result');

    for (const route of ['/my-learning', '/learning/e2e-browser-learning-path', '/learning/lessons/e2e-browser-lesson-1']) {
        await page.goto(route);
        await expect(page.locator('main')).toBeVisible();
        await expectNoSeriousAxeViolations(page, route);
    }
});


test('assessment timer announces meaningful thresholds without announcing every tick', async ({ page }) => {
    await page.clock.install();
    await page.goto('/tests');
    await page.locator('[data-testid^="test-card-"]').first().click();
    await page.getByTestId('start-test-btn').click();

    const timer = page.getByTestId('test-timer');
    const announcements = page.locator('[role="status"][aria-live="assertive"]');
    await expect(timer).toHaveAttribute('role', 'timer');
    await expect(timer).toHaveAttribute('aria-live', 'off');
    await expect(announcements).toBeEmpty();

    const [minutes, seconds] = (await timer.textContent()).trim().split(':').map(Number);
    const initialRemainingSeconds = minutes * 60 + seconds;
    expect(initialRemainingSeconds).toBeGreaterThan(300);

    await page.clock.runFor((initialRemainingSeconds - 300) * 1000);
    await expect(announcements).toContainText('Au mai rămas cinci minute');
    await page.clock.runFor(4 * 60 * 1000);
    await expect(timer).toHaveText('1:00');
    await expect(announcements).toContainText('A mai rămas un minut');
});

test('core learner pages reflow without horizontal overflow on narrow screens', async ({ page }) => {
    for (const width of [320, 390, 768]) {
        await page.setViewportSize({ width, height: 844 });
        for (const route of ['/', '/resources', '/login', '/resources/a2-listening-at-the-restaurant']) {
            await page.goto(route);
            const dimensions = await page.evaluate(() => ({
                viewport: document.documentElement.clientWidth,
                content: document.documentElement.scrollWidth,
            }));
            expect(dimensions.content, `${route} overflows at ${width}px`).toBeLessThanOrEqual(dimensions.viewport);
        }
    }
});
