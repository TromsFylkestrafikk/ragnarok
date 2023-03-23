/**
 * plugins/index.js
 */

import vuetify from './vuetify';
import { ZiggyVue } from '../../../vendor/tightenco/ziggy/dist/vue.m';

export default function registerPlugins(app) {
    app.use(ZiggyVue, document.Ziggy)
        .use(vuetify);
}
