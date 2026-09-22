/**
 * Локальный просмотр фронтенда без запуска бэкенда.
 *
 * Отдаёт собранные файлы из public/, а всё остальное проксирует на
 * боевой сервер. Благодаря этому интерфейс можно смотреть и править
 * локально, работая при этом с настоящими данными.
 *
 * Запуск: npm run preview
 * Открыть: http://localhost:3000
 *
 * Важно: правки фронтенда появятся после повторной сборки,
 * потому что горячая перезагрузка требует Node 20.19 или новее.
 */
import http from 'node:http';
import { readFile, stat } from 'node:fs/promises';
import { extname, join, normalize } from 'node:path';

const PORT = Number(process.env.PREVIEW_PORT ?? 3000);
const BACKEND = process.env.PREVIEW_BACKEND ?? 'https://154-41-135-175.sslip.io';
const PUBLIC_DIR = join(process.cwd(), 'public');

const MIME = {
    '.js': 'text/javascript',
    '.mjs': 'text/javascript',
    '.css': 'text/css',
    '.json': 'application/json',
    '.png': 'image/png',
    '.jpg': 'image/jpeg',
    '.jpeg': 'image/jpeg',
    '.svg': 'image/svg+xml',
    '.webp': 'image/webp',
    '.ico': 'image/x-icon',
    '.woff': 'font/woff',
    '.woff2': 'font/woff2',
};

/** Отдаёт файл из public/, если он там есть. */
async function serveStatic(pathname, res) {
    // normalize отсекает попытки выйти за пределы public/
    const safe = normalize(pathname).replace(/^(\.\.[/\\])+/, '');
    const filePath = join(PUBLIC_DIR, safe);

    if (!filePath.startsWith(PUBLIC_DIR)) {
        return false;
    }

    try {
        const info = await stat(filePath);

        if (!info.isFile()) {
            return false;
        }

        const body = await readFile(filePath);

        res.writeHead(200, {
            'Content-Type': MIME[extname(filePath)] ?? 'application/octet-stream',
            'Cache-Control': 'no-cache',
        });
        res.end(body);

        return true;
    } catch {
        return false;
    }
}

/** Передаёт запрос боевому серверу и возвращает его ответ как есть. */
async function proxy(req, res) {
    const chunks = [];

    for await (const chunk of req) {
        chunks.push(chunk);
    }

    const body = chunks.length ? Buffer.concat(chunks) : undefined;

    const headers = { ...req.headers };
    delete headers.host;
    delete headers['accept-encoding'];

    try {
        const upstream = await fetch(BACKEND + req.url, {
            method: req.method,
            headers,
            body,
            redirect: 'manual',
        });

        const out = Object.fromEntries(upstream.headers.entries());
        delete out['content-encoding'];
        delete out['content-length'];
        delete out['transfer-encoding'];

        // Перенаправления тоже указывают на боевой адрес: после входа
        // браузер иначе ушёл бы с локального просмотра на сервер.
        for (const key of ['location', 'x-inertia-location']) {
            if (out[key]) {
                out[key] = out[key].replace(BACKEND, `http://localhost:${PORT}`);
            }
        }

        // Cookie выданы боевым доменом, поэтому снимаем привязку к нему
        const cookies = upstream.headers.getSetCookie?.() ?? [];

        if (cookies.length) {
            out['set-cookie'] = cookies.map((c) =>
                c.replace(/;\s*domain=[^;]*/gi, '').replace(/;\s*secure/gi, ''),
            );
        }

        const type = upstream.headers.get('content-type') ?? '';
        let payload = Buffer.from(await upstream.arrayBuffer());

        // Боевой сервер проставляет в разметке свои абсолютные адреса.
        // Переписываем их на локальные, иначе браузер загрузит сборку
        // с сервера и местные правки фронтенда не будут видны.
        if (type.includes('text/html')) {
            payload = Buffer.from(
                payload.toString('utf8').replaceAll(BACKEND, `http://localhost:${PORT}`),
                'utf8',
            );
            out['content-type'] = 'text/html; charset=utf-8';
        }

        res.writeHead(upstream.status, out);
        res.end(payload);
    } catch (error) {
        res.writeHead(502, { 'Content-Type': 'text/plain; charset=utf-8' });
        res.end(`Бэкенд недоступен: ${error.message}\nПроверяли: ${BACKEND}`);
    }
}

http.createServer(async (req, res) => {
    const pathname = decodeURIComponent(new URL(req.url, 'http://x').pathname);

    if (req.method === 'GET' && (await serveStatic(pathname, res))) {
        return;
    }

    await proxy(req, res);
}).listen(PORT, () => {
    console.log(`\n  Просмотр фронтенда: http://localhost:${PORT}`);
    console.log(`  Данные берутся с:   ${BACKEND}\n`);
    console.log('  После правок фронтенда выполните: npm run build\n');
});
