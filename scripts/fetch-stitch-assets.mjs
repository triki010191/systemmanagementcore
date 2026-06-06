#!/usr/bin/env node
/**
 * Download Stitch screen assets for HFNMS.
 *
 * Requires STITCH_API_KEY in environment.
 * Get key: https://stitch.withgoogle.com → Settings → API Keys
 *
 * Usage:
 *   STITCH_API_KEY=your_key node scripts/fetch-stitch-assets.mjs
 */

import { mkdir, writeFile } from 'node:fs/promises';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';
import { execSync } from 'node:child_process';

const __dirname = dirname(fileURLToPath(import.meta.url));
const ROOT = join(__dirname, '..');
const OUTPUT_DIR = join(ROOT, 'design', 'stitch');

const PROJECT_ID = '14513084044404878557';

const SCREENS = [
    { id: 'asset-stub-assets_3a96f71153e44449b0c73cee156b999f', slug: '01-design-system', title: 'Design System' },
    { id: 'c8f1407c758b4d948c2ad9f975d2ba34', slug: '02-login', title: 'Login System' },
    { id: '234583316c304dfb82e0349d293ae935', slug: '03-dashboard-eksekutif', title: 'Dasbor Eksekutif' },
    { id: '5ec4d33bb82d4577a4b142910d5a1c0d', slug: '04-alat-penelusuran-jalur', title: 'Alat Penelusuran Jalur' },
    { id: '6fc50d19ed42423e94d615fe08b2261c', slug: '05-manajemen-kabel', title: 'Manajemen Kabel' },
    { id: 'e44acdc913604f55b3859fe37786b8a5', slug: '06-peta-jaringan-gis', title: 'Peta Jaringan GIS' },
];

const API_KEY = process.env.STITCH_API_KEY;

if (!API_KEY) {
    console.error('ERROR: STITCH_API_KEY is not set.');
    console.error('Get your key at https://stitch.withgoogle.com → Settings → API Keys');
    process.exit(1);
}

async function stitchCall(toolName, args) {
    const body = JSON.stringify({
        jsonrpc: '2.0',
        id: Date.now(),
        method: 'tools/call',
        params: { name: toolName, arguments: args },
    });

    const response = await fetch('https://stitch.googleapis.com/mcp', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Goog-Api-Key': API_KEY,
        },
        body,
    });

    const data = await response.json();

    if (data.result?.isError) {
        const msg = data.result.content?.[0]?.text ?? 'Unknown Stitch API error';
        throw new Error(msg);
    }

    const text = data.result?.content?.[0]?.text;
    if (!text) {
        throw new Error(`Empty response from ${toolName}`);
    }

    return JSON.parse(text);
}

async function downloadFile(url, destPath) {
    execSync(`curl -fsSL "${url}" -o "${destPath}"`, { stdio: 'inherit' });
}

async function fetchScreen(screen) {
    const screenDir = join(OUTPUT_DIR, screen.slug);
    await mkdir(screenDir, { recursive: true });

    console.log(`\n→ Fetching: ${screen.title} (${screen.id})`);

    const result = await stitchCall('get_screen', {
        name: `projects/${PROJECT_ID}/screens/${screen.id}`,
        projectId: PROJECT_ID,
        screenId: screen.id,
    });

    const meta = {
        projectId: PROJECT_ID,
        screenId: screen.id,
        title: screen.title,
        slug: screen.slug,
        fetchedAt: new Date().toISOString(),
        screen: result,
    };

    await writeFile(join(screenDir, 'metadata.json'), JSON.stringify(meta, null, 2));

    const downloads = [];

    if (result.htmlCode?.downloadUrl) {
        const htmlPath = join(screenDir, 'screen.html');
        await downloadFile(result.htmlCode.downloadUrl, htmlPath);
        downloads.push('screen.html');
    }

    if (result.screenshot?.downloadUrl) {
        const ext = result.screenshot.mimeType?.includes('png') ? 'png' : 'jpg';
        const imgPath = join(screenDir, `screenshot.${ext}`);
        await downloadFile(result.screenshot.downloadUrl, imgPath);
        downloads.push(`screenshot.${ext}`);
    }

    if (result.figmaExport?.downloadUrl) {
        const figPath = join(screenDir, 'export.fig');
        await downloadFile(result.figmaExport.downloadUrl, figPath);
        downloads.push('export.fig');
    }

    console.log(`  ✓ Saved to design/stitch/${screen.slug}/`);
    console.log(`  ✓ Files: ${downloads.join(', ') || 'metadata only'}`);

    return { screen, downloads };
}

async function main() {
    await mkdir(OUTPUT_DIR, { recursive: true });

    console.log('HFNMS Stitch Asset Fetcher');
    console.log(`Project: ${PROJECT_ID}`);
    console.log(`Output:  design/stitch/`);

    const results = [];

    for (const screen of SCREENS) {
        try {
            results.push(await fetchScreen(screen));
        } catch (error) {
            console.error(`  ✗ Failed: ${error.message}`);
            results.push({ screen, error: error.message });
        }
    }

    const index = {
        projectId: PROJECT_ID,
        fetchedAt: new Date().toISOString(),
        screens: results.map((r) => ({
            slug: r.screen.slug,
            title: r.screen.title,
            screenId: r.screen.id,
            downloads: r.downloads ?? [],
            error: r.error ?? null,
        })),
    };

    await writeFile(join(OUTPUT_DIR, 'index.json'), JSON.stringify(index, null, 2));
    console.log('\n✓ Index written to design/stitch/index.json');
}

main().catch((err) => {
    console.error('Fatal:', err.message);
    process.exit(1);
});
