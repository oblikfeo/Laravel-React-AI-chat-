import { chromium } from 'playwright';

const base = 'https://154-41-135-175.sslip.io';
const dir = 'C:/Users/kfcku/AppData/Local/Temp/claude/c--Users-kfcku-Desktop-Minsk/714ac2e3-bda0-4551-99dc-8243bc5d0bd1/scratchpad';

const browser = await chromium.launch();
const page = await browser.newPage({ viewport: { width: 1440, height: 900 } });

const stamp = Date.now();
await page.goto(`${base}/auth`, { waitUntil: 'networkidle' });
await page.getByRole('button', { name: /sign up|create/i }).first().click();
await page.waitForSelector('input[name="name"]');
await page.fill('input[name="name"]', 'Plan Tester');
await page.fill('input[name="email"]', `plan${stamp}@example.com`);
await page.fill('input[name="password"]', 'password123');
await page.click('button[type="submit"]');
await page.waitForLoadState('networkidle');
await page.waitForTimeout(900);

// 1. Меню
await page.locator('aside button[aria-haspopup="menu"]').click();
await page.waitForTimeout(500);
console.log('пункты меню:', (await page.locator('[role="menu"] [role="menuitem"]').allTextContents()).map((t) => t.trim()));
await page.screenshot({ path: `${dir}/menu.png` });

// 2. Тарифы
await page.getByRole('menuitem', { name: /upgrade/i }).click();
await page.waitForTimeout(800);
await page.screenshot({ path: `${dir}/plans.png` });

const monthly = await page.locator('[role="dialog"] span.text-\\[40px\\]').allTextContents();
console.log('цены за месяц:', monthly);

await page.getByRole('button', { name: /^Yearly/ }).click();
await page.waitForTimeout(400);
console.log('цены за год: ', await page.locator('[role="dialog"] span.text-\\[40px\\]').allTextContents());

// Текущий тариф отмечен?
const disabled = await page.locator('[role="dialog"] button:disabled').allTextContents();
console.log('недоступная кнопка (текущий тариф):', disabled.map((t) => t.trim()));

// 3. Закрытие по Escape
await page.keyboard.press('Escape');
await page.waitForTimeout(300);
await page.getByRole('button', { name: 'Close' }).click().catch(() => {});
await page.waitForTimeout(400);
console.log('закрылось:', (await page.locator('[role="dialog"]').count()) === 0 ? 'да' : 'нет');

// 4. Мобильная
await page.setViewportSize({ width: 390, height: 844 });
await page.goto(base, { waitUntil: 'networkidle' });
await page.waitForTimeout(600);
await page.getByRole('button', { name: /open menu/i }).click();
await page.waitForTimeout(600);
await page.screenshot({ path: `${dir}/mobile-menu.png` });

await browser.close();
