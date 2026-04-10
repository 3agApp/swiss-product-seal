<?php

namespace Deployer;

require 'recipe/laravel.php';

set('repository', getenv('DEPLOY_REPOSITORY') ?: 'https://github.com/SourovCodes/3ag-compliance.git');
set('branch', getenv('DEPLOY_BRANCH') ?: 'main');
set('keep_releases', 2);

set('shared_dirs', [
    'storage',
]);

set('shared_files', [
    '.env',
]);

set('writable_dirs', [
    'storage',
    'bootstrap/cache',
]);

set('writable_mode', 'chmod');

$hostname = getenv('DEPLOY_HOSTNAME');
$deployPath = getenv('DEPLOY_PATH');
$remoteUser = getenv('DEPLOY_REMOTE_USER');
$sshPort = getenv('DEPLOY_SSH_PORT') ?: '22';

if (! $hostname) {
    throw new \RuntimeException('DEPLOY_HOSTNAME environment variable is required');
}

if (! $deployPath) {
    throw new \RuntimeException('DEPLOY_PATH environment variable is required');
}

if (! $remoteUser) {
    throw new \RuntimeException('DEPLOY_REMOTE_USER environment variable is required');
}

host($hostname)
    ->set('remote_user', $remoteUser)
    ->set('deploy_path', $deployPath)
    ->set('http_user', 'www-data')
    ->set('port', $sshPort);

task('build:assets', function () {
    writeln('Building client and SSR bundles locally...');

    runLocally('npm ci');
    runLocally('npm run build:ssr');
})->desc('Build frontend assets locally');

task('upload:assets', function () {
    writeln('Uploading built assets...');

    $archive = 'deploy-assets.tar.gz';
    $user = get('remote_user');
    $hostname = currentHost()->getHostname();
    $port = get('port');
    $releasePath = get('release_path');

    runLocally("tar -czf {$archive} public/build bootstrap/ssr");
    runLocally("scp -P {$port} {$archive} {$user}@{$hostname}:{$releasePath}/");

    run("mkdir -p {$releasePath}/public {$releasePath}/bootstrap");
    run("tar -xzf {$releasePath}/{$archive} -C {$releasePath}");
    runLocally("rm {$archive}");
    run("rm {$releasePath}/{$archive}");
})->desc('Upload built assets');

task('queue:restart', function () {
    writeln('Gracefully restarting queue workers...');

    run('cd {{release_path}} && php artisan queue:restart');
})->desc('Restart queue workers');

before('deploy', 'build:assets');
after('deploy:vendors', 'upload:assets');
after('deploy:symlink', 'queue:restart');
after('deploy:failed', 'deploy:unlock');
