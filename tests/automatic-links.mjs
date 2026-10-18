/* Friends-SNCMS · Andreas Holzer @WebCrew (GitHub) · Copyright © 2026 Andreas Holzer. */
import assert from 'node:assert/strict';
import {readFile} from 'node:fs/promises';
import {firstLink} from '../public/assets/link-preview.js';
const cases=JSON.parse(await readFile(new URL('./link-cases.json',import.meta.url),'utf8'));
for(const [body,expected] of cases) assert.equal(firstLink(body),expected);
console.log(cases.length+' browser URL extraction checks passed. No network requests.');
