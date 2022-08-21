/**
 * @requires Laravel
 */

const fs = require('fs');
const { exec } = require('child_process');

let debounce = null;
let config = [];

// Load the configuration file and boot the watcher.
fs.readFile(__dirname + '/watch.json', (err, data) => {
    config = JSON.parse(data);
    boot();
});

// Run the generate command
function triggerRun() {
    debounce = setTimeout(() => {
        const cmd = 'php ' + config.artisan + ' docwatch:generate';
        console.log("\n\n\nRunning: " + cmd);

        exec(cmd).stdout.pipe(process.stdout);;
    }, 200);
}

// Run after the configuration file is loaded.
function boot() {
    // Standardise the list of paths to watch
    const paths = {};
    config.rules.forEach((rule) => {
        const rulePaths = Array.isArray(rule.path) ? rule.path : [rule.path];

        rulePaths.forEach((path) => paths[path] = path);
    });

    // Iterate each path and watch them individually
    Object.keys(paths).forEach((path) => {
        fs.watch(path, () => {
            if (debounce !== null) {
                clearTimeout(debounce);
            }

            triggerRun();
        });

        console.log("Watching directory: " + path);
    });

    triggerRun();
}

