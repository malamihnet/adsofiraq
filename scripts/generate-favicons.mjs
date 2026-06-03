import sharp from 'sharp';
import { existsSync } from 'fs';

const source = 'public/favicon-96x96.png';

if (!existsSync(source)) {
    console.error('Missing', source);
    process.exit(1);
}

for (const size of [16, 32]) {
    const target = `public/favicon-${size}x${size}.png`;
    await sharp(source).resize(size, size).png().toFile(target);
    console.log('Wrote', target);
}
