const fs = require('fs');
const path = require('path');
const AdmZip = require('adm-zip');

const rootDir = path.resolve(__dirname, '..');
const pluginDir = path.join(rootDir, 'yasmine-artistry-booking');
const publicDir = path.join(rootDir, 'public');

console.log('Packaging plugin version 1.2...');

// Ensure public directory exists
if (!fs.existsSync(publicDir)) {
  fs.mkdirSync(publicDir, { recursive: true });
}

// 1. Create AdmZip instance with explicit folder hierarchy
const zip = new AdmZip();

// Explicitly register root directory entry
zip.addFile('yasmine-artistry-booking/', Buffer.alloc(0));

function addDirectoryToZip(dirPath, zipPrefix) {
  const entries = fs.readdirSync(dirPath, { withFileTypes: true });
  for (const entry of entries) {
    const fullPath = path.join(dirPath, entry.name);
    const zipPath = `${zipPrefix}/${entry.name}`;
    if (entry.isDirectory()) {
      zip.addFile(`${zipPath}/`, Buffer.alloc(0));
      addDirectoryToZip(fullPath, zipPath);
    } else {
      const fileBuffer = fs.readFileSync(fullPath);
      zip.addFile(zipPath, fileBuffer);
    }
  }
}

addDirectoryToZip(pluginDir, 'yasmine-artistry-booking');

// Write zip files to public/
const targetZips = [
  path.join(publicDir, 'yasmine-artistry-booking.zip'),
  path.join(publicDir, 'faiiya-booking-plugin.zip'),
  path.join(publicDir, 'faiiya-booking.zip')
];

for (const zipPath of targetZips) {
  zip.writeZip(zipPath);
  console.log(`Saved: ${path.relative(rootDir, zipPath)} (${(fs.statSync(zipPath).size / 1024).toFixed(1)} KB)`);
}

// 2. Read key files for src/plugin-code.ts
const codeFileRelPaths = [
  { path: 'yasmine-artistry-booking.php', desc: 'Main WordPress bootstrap file initializing database, REST endpoints, hooks, cron schedules, and Elementor integration.' },
  { path: 'includes/class-elementor.php', desc: 'Custom Elementor Page Builder widget with live visual controls for drag-and-drop booking forms.' },
  { path: 'frontend/views/booking-form.php', desc: 'Multi-step frontend booking form template with horizontal stepper, category filters, and calendar.' },
  { path: 'assets/js/yab-frontend.js', desc: 'Frontend controller managing step transitions without scrolling, monthly calendar picker, and Paystack integration.' },
  { path: 'assets/css/yab-frontend.css', desc: 'Frontend stylesheet featuring rounded borders, box shadows, lazy animations, and horizontal stepper.' },
  { path: 'admin/class-admin-services.php', desc: 'Service manager supporting image upload & selection from WordPress media library.' },
  { path: 'admin/views/services.php', desc: 'Admin service catalog view with image preview and media picker modal.' },
  { path: 'includes/class-booking.php', desc: 'Core booking transaction engine with atomic slot locks and payment status reconciliation.' },
  { path: 'includes/class-paystack.php', desc: 'Paystack payment gateway with webhook verification and partial deposit calculation.' },
  { path: 'includes/class-calendar.php', desc: 'Google Calendar synchronization and ICS event generation engine.' }
];

let generatedTs = `export interface PluginFile {
  path: string;
  description: string;
  content: string;
}

export const pluginCodeFiles: PluginFile[] = [\n`;

for (const item of codeFileRelPaths) {
  const fullPath = path.join(pluginDir, item.path);
  if (fs.existsSync(fullPath)) {
    const rawContent = fs.readFileSync(fullPath, 'utf8');
    const escapedContent = JSON.stringify(rawContent);
    generatedTs += `  {\n    path: ${JSON.stringify(item.path)},\n    description: ${JSON.stringify(item.desc)},\n    content: ${escapedContent}\n  },\n`;
  }
}

generatedTs += `];\n`;

fs.writeFileSync(path.join(rootDir, 'src', 'plugin-code.ts'), generatedTs, 'utf8');
console.log('Successfully updated src/plugin-code.ts!');
console.log('Plugin repackage v1.2 complete!');
