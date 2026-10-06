import fs from "node:fs/promises";
import { fileURLToPath } from "node:url";
import { FileBlob, SpreadsheetFile } from "@oai/artifact-tool";

const sourcePath = "C:/Users/THINKPAD/Desktop/prospek.xlsx";
const outputDir = fileURLToPath(new URL("./", import.meta.url));

const input = await FileBlob.load(sourcePath);
const workbook = await SpreadsheetFile.importXlsx(input);

const summary = await workbook.inspect({
  kind: "workbook,sheet,table,region",
  maxChars: 20000,
  tableMaxRows: 30,
  tableMaxCols: 20,
  tableMaxCellChars: 160,
});
console.log(summary.ndjson);

for (const sheet of workbook.worksheets.items) {
  const used = sheet.getUsedRange();
  console.log(JSON.stringify({
    sheet: sheet.name,
    usedRange: used?.address ?? null,
    values: used?.values ?? [],
    formulas: used?.formulas ?? [],
  }));

  const preview = await workbook.render({
    sheetName: sheet.name,
    autoCrop: "all",
    scale: 1.5,
    format: "png",
  });
  const safeName = sheet.name.replace(/[^a-z0-9_-]+/gi, "-");
  await fs.writeFile(
    `${outputDir}/before-${safeName}.png`,
    new Uint8Array(await preview.arrayBuffer()),
  );
}
