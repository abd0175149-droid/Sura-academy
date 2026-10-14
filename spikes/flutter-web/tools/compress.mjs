// WP-0.5: precompress every text/wasm/font file of the given web builds (brotli q11 + gzip -9,
// as a CDN would serve them) and print a CSV of raw/gzip/brotli bytes per file to stdout.
// index.html is skipped: the spike server rewrites it per request.
import { readdirSync, readFileSync, statSync, writeFileSync } from 'node:fs';
import { join, relative, extname, basename } from 'node:path';
import { brotliCompressSync, gzipSync, constants } from 'node:zlib';

const COMPRESS = new Set(['.js', '.mjs', '.wasm', '.json', '.css', '.ttf', '.otf', '.bin', '.frag', '.symbols']);

const walk = (dir) => readdirSync(dir).flatMap((name) => {
  const path = join(dir, name);
  return statSync(path).isDirectory() ? walk(path) : [path];
});

console.log('build,file,raw_bytes,gzip_bytes,brotli_bytes');
for (const root of process.argv.slice(2)) {
  for (const file of walk(root).sort()) {
    const ext = extname(file);
    if (ext === '.br' || ext === '.gz' || basename(file) === 'index.html') continue;
    const raw = readFileSync(file);
    let gz = '';
    let br = '';
    if (COMPRESS.has(ext)) {
      const g = gzipSync(raw, { level: 9 });
      const b = brotliCompressSync(raw, { params: { [constants.BROTLI_PARAM_QUALITY]: 11, [constants.BROTLI_PARAM_SIZE_HINT]: raw.length } });
      writeFileSync(`${file}.gz`, g);
      writeFileSync(`${file}.br`, b);
      gz = g.length;
      br = b.length;
    }
    console.log(`${basename(root)},${relative(root, file)},${raw.length},${gz},${br}`);
  }
}
