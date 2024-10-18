const Encore = require('@symfony/webpack-encore');

Encore
    .setOutputPath('public/build')
    .setPublicPath('/build')
    .enableSourceMaps(!Encore.isProduction())
    .enableTypeScriptLoader()
    .addEntry('scripts/main', [
        './assets/scripts/points.tsx',
        './assets/scripts/tags.tsx',
        './assets/scripts/profilesettings.tsx',
    ])
    .addEntry('scripts/admin', [
        './assets/scripts/admin.tsx',
    ])
    .addEntry('scripts/pet', [
        './assets/scripts/pet.tsx'
    ])
    .addEntry('scripts/profile', [
        './assets/scripts/profile.tsx'
    ])
    .addEntry('scripts/legacy', [
        './assets/scripts/filtering.js',
        './assets/scripts/sorting.js',
        './assets/scripts/list.js',
    ])
    .addStyleEntry('css/main', './assets/css/main.scss')
    .copyFiles({
        from: './assets/images',
        to: 'images/[path][name].[hash:8].[ext]',
        pattern: /default_pic.png/,
    })
    .copyFiles({
        from: './assets/images/releases',
        to: 'images/releases/[path][name].[hash:8].[ext]',
    })
    .disableSingleRuntimeChunk()
    .enableSassLoader()
    .enableVersioning()
    .cleanupOutputBeforeBuild();

module.exports = Encore.getWebpackConfig();