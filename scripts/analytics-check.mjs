// End-to-end analytics check: loads pages in headless Chrome, grants consent
// the same way the banner does, and reports GA4 hits (google-analytics.com
// /g/collect) with their measurement ID and event name.
// Usage: node scripts/analytics-check.mjs [base-url]
import puppeteer from '/opt/lighthouse/node_modules/puppeteer-core/lib/puppeteer/puppeteer-core.js';

const base = process.argv[2] || 'https://observatory.raiuz.site';
const browser = await puppeteer.launch({
  executablePath: '/usr/bin/google-chrome',
  args: ['--headless=new', '--no-sandbox', '--disable-gpu'],
});
const page = await browser.newPage();
const hits = [];
page.on('request', (req) => {
  const url = new URL(req.url());
  if (url.pathname.endsWith('/collect') && /google/.test(url.hostname)) {
    const params = new URLSearchParams(url.search + '&' + (req.postData() || ''));
    hits.push({ host: url.hostname, tid: params.get('tid'), en: params.get('en'), gcs: params.get('gcs'), dl: params.get('dl') });
  }
});

// 1. First visit: no consent yet. Banner should show; hits must not set cookies.
await page.goto(base + '/', { waitUntil: 'networkidle0' });
const bannerShown = await page.$('.odo-consent') !== null;
const before = hits.length;

// 2. Click "Allow analytics", then browse two pages.
await page.click('.odo-consent [data-choice="granted"]');
await new Promise((r) => setTimeout(r, 3000));
await page.goto(base + '/datasets', { waitUntil: 'networkidle0' });
await page.goto(base + '/datasets/lbn/sp-pop-totl', { waitUntil: 'networkidle0' });
await new Promise((r) => setTimeout(r, 5000));
await page.goto('about:blank');
await new Promise((r) => setTimeout(r, 2000));
const cookies = (await page.cookies(base)).map((c) => c.name).filter((n) => n.startsWith('_ga'));

console.log(`banner shown on first visit: ${bannerShown}`);
console.log(`hits before consent: ${before} (gcs=G100 means storage denied, no cookies)`);
for (const h of hits) {
  console.log(`hit: ${h.host} tid=${h.tid} event=${h.en} consent=${h.gcs} page=${(h.dl || '').replace(base, '') || '/'}`);
}
console.log(`GA cookies after consent: ${cookies.join(', ') || 'none'}`);
await browser.close();
