let mix = require('laravel-mix');

/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Laravel application. By default, we are compiling the Sass
 | file for the application as well as bundling up all the JS files.
 |
 */

mix.webpackConfig({
    devtool: "source-map"
});

mix.react('resources/js/app.js', 'public/js')
    .sass('resources/sass/app.scss', 'public/css');
//mix.sass('resources/assets/sass/partnerV2/icons.scss', 'public/_partnerV2/css/').sourceMaps();
mix.sass('resources/assets/sass/partnerV2/style.scss', 'public/_partnerV2/css/').sourceMaps();

mix.babel([
    'resources/assets/js/front/jquery.min.js',
    'resources/assets/js/front/jquery-migrate-3.0.1.min.js',
    'resources/assets/js/front/modernizr.js',
    'resources/assets/js/front/bootstrap.min.js',
    'resources/assets/js/front/jquery.nav.js',
    'resources/assets/js/front/owl.carousel.js',
    'resources/assets/js/front/visible.js',
    'resources/assets/js/front/jquery.stellar.min.js',
    'resources/assets/js/front/jquery.countTo.js',
    'resources/assets/js/front/imagesloaded.pkgd.min.js',
    'resources/assets/js/front/isotope.pkgd.min.js',
    'resources/assets/js/front/jquery.magnific-popup.js',
    'resources/assets/js/front/jquery.ajaxchimp.min.js',
    'resources/assets/js/front/plyr.js',
    'resources/assets/js/front/slick.min.js',
    'resources/assets/js/front/blazy.min.js',
    'resources/assets/js/front/custom.js',
], 'public/front-assets/js/all.js').sourceMaps();

/*
    'public/front-assets/assets/js/src/isInViewport.min.js',
    'public/front-assets/assets/js/src/jquery.countdown.min.js',
    'public/front-assets/assets/js/src/typer.js',

 */


mix.sass('resources/assets/sass/front/style.scss', 'public/front-assets/css').sourceMaps();
mix.sass('resources/assets/sass/front/responsive.scss', 'public/front-assets/css').sourceMaps();

mix.styles([
    'resources/assets/css/front/bootstrap.min.css',
    'resources/assets/css/front/font-awesome.min.css',
    'resources/assets/css/front/themify-icons.css',
    'resources/assets/css/front/magnific-popup.css',
    'resources/assets/css/front/owl.carousel.css',
    'resources/assets/css/front/owl.transitions.css',
    'resources/assets/css/front/plyr.css',
    'resources/assets/css/front/swiper.min.css',
    'resources/assets/css/front/slick.css',
    'resources/assets/css/front/red-blue.css',
    'public/front-assets/css/style.css',
    'resources/assets/css/front/preloader.css',
    'public/front-assets/css/responsive.css'
], 'public/front-assets/css/all.css').sourceMaps();


