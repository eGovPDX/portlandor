import fs from "fs";
import path from "path";

const sourceDir = path.join(
  process.cwd(),
  "node_modules/@cityofportland/components-drupal/dist/components",
);
const targetDir = path.join(process.cwd(), "components");

// Delete target directory if it already exists, to clear old files
if (fs.existsSync(targetDir)) {
  fs.rmSync(targetDir, { recursive: true });
}

fs.mkdirSync(targetDir, { recursive: true });

const copyDir = (src, dest) => {
  if (!fs.existsSync(dest)) {
    fs.mkdirSync(dest, { recursive: true });
  }

  const files = fs.readdirSync(src);
  files.forEach((file) => {
    const srcPath = path.join(src, file);
    const destPath = path.join(dest, file);
    const stat = fs.statSync(srcPath);

    if (stat.isDirectory()) {
      copyDir(srcPath, destPath);
    } else {
      fs.copyFileSync(srcPath, destPath);
    }
  });
};

try {
  copyDir(sourceDir, targetDir);
  console.log("Component library successfully copied to components/");
} catch (error) {
  console.error("Error copying files:", error);
  process.exit(1);
}
