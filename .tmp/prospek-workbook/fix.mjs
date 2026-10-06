import fs from "node:fs/promises";
import path from "node:path";
import { FileBlob, SpreadsheetFile } from "@oai/artifact-tool";

const sourcePath = "C:/Users/THINKPAD/Desktop/prospek.xlsx";
const outputDir = path.resolve("outputs/prospek-import-2026-09-28");
const outputPath = path.join(outputDir, "prospek-siap-import.xlsx");
const previewPath = path.join(outputDir, "prospek-siap-import.png");

const input = await FileBlob.load(sourcePath);
const workbook = await SpreadsheetFile.importXlsx(input);
const sheet = workbook.worksheets.getItemAt(0);
const used = sheet.getUsedRange();
const values = used.values;

if (values.length < 2 || values[0]?.[1] !== "Nama Toko") {
  throw new Error("Struktur workbook tidak sesuai: kolom Nama Toko tidak ditemukan di B1.");
}

const cleaned = values.map((row, rowIndex) => row.map((value, columnIndex) => {
  if (typeof value !== "string") return value;
  if (rowIndex === 0 && columnIndex === 5) return "Hasil Analisa";
  return value.replace(/\s+/g, " ").trim();
}));

const normalizedNames = new Map();
const normalizedUrls = new Map();
const validationErrors = [];

for (let index = 1; index < cleaned.length; index += 1) {
  const excelRow = index + 1;
  const name = String(cleaned[index][1] ?? "").trim();
  const url = String(cleaned[index][2] ?? "").trim();

  if (!name) validationErrors.push(`Baris ${excelRow}: Nama Toko kosong.`);
  if (!/^https?:\/\//i.test(url)) validationErrors.push(`Baris ${excelRow}: URL Toko Shopee tidak valid.`);

  const nameKey = name.toLocaleLowerCase("id-ID").replace(/\s+/g, " ");
  const urlKey = url.toLocaleLowerCase("id-ID");
  if (nameKey && normalizedNames.has(nameKey)) {
    validationErrors.push(`Baris ${excelRow}: Nama Toko sama dengan baris ${normalizedNames.get(nameKey)}.`);
  }
  if (urlKey && normalizedUrls.has(urlKey)) {
    validationErrors.push(`Baris ${excelRow}: URL sama dengan baris ${normalizedUrls.get(urlKey)}.`);
  }
  normalizedNames.set(nameKey, excelRow);
  normalizedUrls.set(urlKey, excelRow);
}

if (validationErrors.length) {
  throw new Error(validationErrors.join("\n"));
}

used.values = cleaned;
sheet.name = "Prospek";
sheet.showGridLines = false;
sheet.freezePanes.freezeRows(1);

const fullRange = sheet.getRange(`A1:O${cleaned.length}`);
fullRange.format.font = { name: "Arial", size: 10, color: "#1F2937" };
fullRange.format.verticalAlignment = "center";
fullRange.format.rowHeight = 30;

const header = sheet.getRange("A1:O1");
header.format = {
  fill: "#293681",
  font: { name: "Arial", size: 10, bold: true, color: "#FFFFFF" },
  horizontalAlignment: "center",
  verticalAlignment: "center",
  wrapText: true,
  rowHeight: 32,
  borders: { preset: "inside", style: "thin", color: "#FFFFFF" },
};

sheet.getRange(`A2:A${cleaned.length}`).format.horizontalAlignment = "center";
sheet.getRange(`A2:A${cleaned.length}`).format.numberFormat = "0";
sheet.getRange(`M2:M${cleaned.length}`).format.horizontalAlignment = "center";
sheet.getRange(`M2:M${cleaned.length}`).format.numberFormat = "dd-mmm-yyyy";
sheet.getRange(`N2:N${cleaned.length}`).format.horizontalAlignment = "center";
sheet.getRange(`B2:O${cleaned.length}`).format.wrapText = false;
sheet.getRange(`C2:C${cleaned.length}`).format.wrapText = true;

const widths = {
  A: 10, B: 28, C: 52, D: 20, E: 20,
  F: 22, G: 30, H: 24, I: 24, J: 26,
  K: 22, L: 18, M: 17, N: 20, O: 28,
};
for (const [column, width] of Object.entries(widths)) {
  sheet.getRange(`${column}:${column}`).format.columnWidth = width;
}

const table = sheet.tables.items[0];
if (table) {
  table.name = "ProspekImport";
  table.style = "TableStyleMedium2";
  table.showHeaders = true;
  table.showBandedColumns = false;
  table.showFilterButton = true;
}

workbook.recalculate();

const check = await workbook.inspect({
  kind: "table",
  range: `Prospek!A1:O${cleaned.length}`,
  include: "values,formulas",
  tableMaxRows: 35,
  tableMaxCols: 15,
  maxChars: 24000,
});
console.log(check.ndjson);

const errors = await workbook.inspect({
  kind: "match",
  searchTerm: "#REF!|#DIV/0!|#VALUE!|#NAME\\?|#N/A|#NUM!|#NULL!|#SPILL!|#CALC!",
  options: { useRegex: true, maxResults: 100 },
  summary: "final formula error scan",
});
console.log(errors.ndjson);

await fs.mkdir(outputDir, { recursive: true });
const preview = await workbook.render({
  sheetName: "Prospek",
  range: `A1:O${cleaned.length}`,
  scale: 1.25,
  format: "png",
});
await fs.writeFile(previewPath, new Uint8Array(await preview.arrayBuffer()));

const output = await SpreadsheetFile.exportXlsx(workbook);
await output.save(outputPath);

console.log(JSON.stringify({
  outputPath,
  previewPath,
  rowCount: cleaned.length - 1,
  cleanedName: cleaned[23][1],
}));
