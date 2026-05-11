/**
 * Compiles languages/rrze-shorturl-de_DE.po to .mo files (gettext binary).
 * Used when GNU msgfmt is not available in the environment.
 */
const fs = require('fs');
const path = require('path');
const { po, mo } = require('gettext-parser');

const languagesDir = path.join(__dirname, '..', 'languages');
const poPath = path.join(languagesDir, 'rrze-shorturl-de_DE.po');
const parsed = po.parse(fs.readFileSync(poPath, 'utf8'));
const buffer = mo.compile(parsed);

const targets = [
	path.join(languagesDir, 'rrze-shorturl-de_DE.mo'),
	path.join(languagesDir, 'rrze-shorturl-de_DE_formal.mo'),
];
for (const out of targets) {
	fs.writeFileSync(out, buffer);
}
console.log('Wrote', targets.join(', '));
