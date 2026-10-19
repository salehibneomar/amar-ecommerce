import { readFile, writeFile } from 'node:fs/promises';
import { PDFDocument } from 'pdf-lib';

const [command, source, ...options] = process.argv.slice(2);
const document = await PDFDocument.load(await readFile(source), { ignoreEncryption: true });

if (command === 'count') {
    process.stdout.write(String(document.getPageCount()));
} else if (command === 'trim') {
    const [destination, firstPage, lastPage] = options;
    const last = Math.min(Number(lastPage), document.getPageCount());
    const indices = Array.from({ length: last - Number(firstPage) + 1 }, (_, index) => Number(firstPage) - 1 + index);
    const trimmed = await PDFDocument.create();

    (await trimmed.copyPages(document, indices)).forEach((page) => trimmed.addPage(page));
    await writeFile(destination, await trimmed.save({ useObjectStreams: false }));
} else {
    throw new Error(`Unknown command: ${command}`);
}
