import { build } from 'esbuild';
import { cp, readFile, readdir, writeFile } from 'node:fs/promises';

const result = await build({
    entryPoints: ['resources/js/service-devices.js'],
    bundle: true,
    minify: true,
    legalComments: 'eof',
    metafile: true,
    outfile: 'public/assets/js/service-devices.js',
});
const packages = new Set(Object.keys(result.metafile.inputs)
    .filter((path) => path.startsWith('node_modules/'))
    .map((path) => {
        const parts = path.split('/');
        return parts.slice(0, parts[1].startsWith('@') ? 3 : 2).join('/');
    }));
const notices = [];
for (const directory of [...packages].sort()) {
    const { name, version } = JSON.parse(await readFile(`${directory}/package.json`, 'utf8'));
    const licenses = (await readdir(directory)).filter((file) => /^(licen[cs]e|notice)(\.|$)/i.test(file));
    if (licenses.length === 0) throw new Error(`Missing license notice: ${name}`);
    notices.push(`${name} ${version}\n${(await Promise.all(licenses.sort().map((file) => readFile(`${directory}/${file}`, 'utf8')))).join('\n')}`);
}
await writeFile('public/assets/js/service-devices.LICENSE.txt', notices.join('\n\n========================================\n\n'));

await build({
    entryPoints: ['resources/js/office-map.js'],
    bundle: true,
    minify: true,
    legalComments: 'eof',
    outfile: 'public/assets/js/office-map.js',
});
await cp('node_modules/leaflet/dist/leaflet.css', 'public/assets/css/leaflet.css');
await cp('node_modules/leaflet/dist/images', 'public/assets/css/images', { recursive: true });
await cp('node_modules/leaflet/LICENSE', 'public/assets/js/office-map.LICENSE.txt');
