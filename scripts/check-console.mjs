import { chromium } from '@playwright/test';

const url = process.argv[2] ?? 'https://127.0.0.1:8080';
const browser = await chromium.launch();
const page = await browser.newPage();
page.on('console', (msg) => {
  const { url: locUrl, lineNumber, columnNumber } = msg.location();
  console.log(`[console:${msg.type()}] ${msg.text()} (${locUrl ?? 'unknown'}:${lineNumber ?? ''}:${columnNumber ?? ''})`);
});
page.on('response', (resp) => {
  const status = resp.status();
  if (status >= 400) {
    console.log(`[response:${status}] ${resp.url()}`);
  }
});
await page.goto(url, { waitUntil: 'networkidle' });
await page.waitForTimeout(3000);
await browser.close();
