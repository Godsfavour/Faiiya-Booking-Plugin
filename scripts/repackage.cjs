const fs = require('fs');
const path = require('path');
const AdmZip = require('adm-zip');

const rootDir = path.resolve(__dirname, '..');
const pluginDir = path.join(rootDir, 'yasmine-artistry-booking');
const publicDir = path.join(rootDir, 'public');

console.log('Packaging Faiiya Booking plugin...');

// Ensure public directory exists
if (!fs.existsSync(publicDir)) {
  fs.mkdirSync(publicDir, { recursive: true });
}

// Format timestamp: YYYYMMDD-HHmmss
const now = new Date();
const year = now.getFullYear();
const month = String(now.getMonth() + 1).padStart(2, '0');
const day = String(now.getDate()).padStart(2, '0');
const hours = String(now.getHours()).padStart(2, '0');
const minutes = String(now.getMinutes()).padStart(2, '0');
const seconds = String(now.getSeconds()).padStart(2, '0');
const timestamp = `${year}${month}${day}-${hours}${minutes}${seconds}`;

// The single unified file name agreed with the user
const staticZipName = 'Faiiya booking.zip';
const timestampedZipName = `Faiiya-booking-${timestamp}.zip`;

// 1. Remove all old/legacy zip files in public to keep it clean and unambiguous
const existingFiles = fs.readdirSync(publicDir);
for (const file of existingFiles) {
  if (file.endsWith('.zip') || file.toLowerCase().includes('booking')) {
    try {
      fs.unlinkSync(path.join(publicDir, file));
      console.log(`Removed old zip: ${file}`);
    } catch (err) {
      console.error(`Failed to delete ${file}:`, err.message);
    }
  }
}

// 2. Create AdmZip instance with explicit folder hierarchy
const zip = new AdmZip();

// Explicitly register root directory entry: yasmine-artistry-booking/
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

// Write the primary single agreed file: 'Faiiya booking.zip'
const staticZipPath = path.join(publicDir, staticZipName);
zip.writeZip(staticZipPath);
const staticSize = (fs.statSync(staticZipPath).size / 1024).toFixed(1);
console.log(`Saved primary: ${staticZipName} (${staticSize} KB)`);

// Also save timestamped copy so user can track version history: Faiiya-booking-YYYYMMDD-HHmmss.zip
const timestampedZipPath = path.join(publicDir, timestampedZipName);
zip.writeZip(timestampedZipPath);
console.log(`Saved timestamped: ${timestampedZipName} (${staticSize} KB)`);

// 3. Write manifest.json in public so frontend can dynamically fetch current filename & timestamp
const manifest = {
  primaryFile: staticZipName,
  timestampedFile: timestampedZipName,
  timestamp: timestamp,
  formattedTime: now.toISOString(),
  sizeKb: staticSize
};
fs.writeFileSync(path.join(publicDir, 'package-info.json'), JSON.stringify(manifest, null, 2), 'utf8');

// 4. Update src/plugin-code.ts for the in-app code viewer
const codeFileRelPaths = [
  { path: 'yasmine-artistry-booking.php', desc: 'Main WordPress bootstrap file initializing database, REST endpoints, hooks, cron schedules, and Elementor integration.' },
  { path: 'includes/class-elementor.php', desc: 'Custom Elementor Page Builder widget with live visual controls for drag-and-drop booking forms.' },
  { path: 'frontend/views/booking-form.php', desc: 'Multi-step frontend booking form template with horizontal stepper, category filters, and calendar.' },
  { path: 'assets/js/yab-frontend.js', desc: 'Frontend controller managing step transitions without scrolling, monthly calendar picker, and Paystack integration.' },
  { path: 'assets/css/yab-frontend.css', desc: 'Frontend stylesheet featuring rounded borders, box shadows, lazy animations, and horizontal stepper.' },
  { path: 'admin/class-admin-services.php', desc: 'Service manager supporting image upload & selection from WordPress media library and linked locations.' },
  { path: 'admin/views/services.php', desc: 'Admin service catalog view with image preview, linked locations checkboxes, and media picker modal.' },
  { path: 'includes/class-service.php', desc: 'Service model managing database records and service-location associations for variable pricing.' },
  { path: 'includes/class-location.php', desc: 'Location model with fee calculation (percentage/fixed) and service filtering.' },
  { path: 'includes/class-pricing.php', desc: 'Dynamic pricing engine calculating base price, location surcharges, and deposit requirements.' },
  { path: 'includes/class-database.php', desc: 'Database schema manager creating tables including yab_service_locations junction table.' },
  { path: 'includes/class-booking.php', desc: 'Core booking transaction engine with atomic slot locks and payment status reconciliation.' },
  { path: 'includes/class-paystack.php', desc: 'Paystack payment gateway with webhook verification and partial deposit calculation.' },
  { path: 'includes/class-calendar.php', desc: 'Google Calendar synchronization and ICS event generation engine.' }
];

let generatedTs = `export interface PluginFile {
  path: string;
  description: string;
  content: string;
}

export const pluginPackageInfo = ${JSON.stringify(manifest, null, 2)};

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
console.log(`Plugin packaging complete: ${staticZipName} and ${timestampedZipName}`);
