const path = require('path');
const Encore = require('@symfony/webpack-encore');

if (!Encore.isRuntimeEnvironmentConfigured()) {
    Encore.configureRuntimeEnvironment(process.env.NODE_ENV || 'dev');
}

Encore
    .setOutputPath('public/build/')
    .setPublicPath('/build')

    .addAliases({
        umbrella_core: path.join(__dirname, '/vendor/umbrella2/corebundle/assets/'),
        umbrella_admin: path.join(__dirname, '/vendor/umbrella2/adminbundle/assets/')
    })

    .addEntry('front', './assets/front/Front.js')
    .addEntry('admin', './assets/admin/Admin.js')
    .addEntry('ckeditor', './vendor/umbrella2/corebundle/assets/ckeditor/ckeditor.js')

    .enableSassLoader()

    .splitEntryChunks()
    .enableSingleRuntimeChunk()
    .cleanupOutputBeforeBuild()
    //.enableBuildNotifications()

    .configureBabel(function (babelConfig) {
        babelConfig.plugins.push('transform-class-properties');
    })

    .copyFiles([
        {
            from: './assets/images',
            to: 'images/[path][name].[ext]'
        },
        {
            from: './vendor/umbrella2/corebundle/assets/images',
            to: 'images/[path][name].[ext]'
        }
    ])

    .enableSourceMaps(!Encore.isProduction())
    .enableVersioning(Encore.isProduction())

    .configureBabelPresetEnv((config) => {
        config.useBuiltIns = 'usage';
        config.corejs = 3;
    });

const config = Encore.getWebpackConfig();

module.exports = config;