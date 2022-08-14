const fs = require('fs');
const { exec } = require('child_process');
const rootFolder = (process.argv.slice(2)[0] !== undefined) ? process.argv.slice(2)[0] : process.cwd();
const modelFolder = rootFolder + '/app/Models';
let debounce = null;

fs.watch(modelFolder, (eventType, filename) => {
    if (debounce !== null) {
        clearTimeout(debounce);
    }

    triggerRun();
});

function triggerRun() {
    debounce = setTimeout(() => {
        const cmd = 'php ' + rootFolder + '/artisan docwatch:generate';
        console.log('-- Generating docs: ' + cmd);

        exec(cmd);
    }, 200);
}

console.log("\nWatching directory: " + modelFolder + "\n");

triggerRun();
