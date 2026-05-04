#!/usr/bin/env node
// Generate public/sw.js from scripts/sw-template.js by injecting the
// list of client assets to precache from the Vite manifest.

import { createHash } from "node:crypto";
import { readFileSync, writeFileSync } from "node:fs";
import { dirname, resolve } from "node:path";
import { fileURLToPath } from "node:url";

const __dirname = dirname(fileURLToPath(import.meta.url));
const projectRoot = resolve(__dirname, "..");

const VITE_BASE = "/build";
const CLIENT_ENTRY = "assets/client/index.jsx";
const MANIFEST_PATH = resolve(projectRoot, "public/build/.vite/manifest.json");
const TEMPLATE_PATH = resolve(__dirname, "sw-template.js");
const OUTPUT_PATH = resolve(projectRoot, "public/client/sw.js");

const manifest = JSON.parse(readFileSync(MANIFEST_PATH, "utf-8"));

const clientEntry = manifest[CLIENT_ENTRY];
if (!clientEntry) {
  throw new Error(`Client entry "${CLIENT_ENTRY}" not found in Vite manifest`);
}

const collectAssets = (entryKey, collected = new Set()) => {
  const entry = manifest[entryKey];
  if (!entry) return collected;

  if (entry.file) collected.add(entry.file);
  for (const css of entry.css ?? []) collected.add(css);
  for (const asset of entry.assets ?? []) collected.add(asset);

  for (const importKey of entry.imports ?? []) {
    collectAssets(importKey, collected);
  }

  return collected;
};

const assets = Array.from(collectAssets(CLIENT_ENTRY))
  .map((file) => `${VITE_BASE}/${file}`)
  .sort();

const buildHash = createHash("sha256")
  .update(assets.join("\n"))
  .digest("hex")
  .slice(0, 16);

const template = readFileSync(TEMPLATE_PATH, "utf-8");

const output = template
  .replace(/"__BUILD_HASH__"/g, JSON.stringify(buildHash))
  .replace(/__PRECACHE_MANIFEST__/g, JSON.stringify(assets, null, 2));

writeFileSync(OUTPUT_PATH, output);

console.log(
  `Generated ${OUTPUT_PATH} (cache: os2display-client-${buildHash}, ${assets.length} assets)`,
);
