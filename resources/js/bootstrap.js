/**
 * We'll load the axios HTTP library which allows us to easily issue requests to
 * our Laravel back-end. This library automatically handles sending the CSRF
 * token as a header based on the value of the "XSRF" token cookie.
 */

import axios from 'axios';
import { configureEcho } from '@laravel/echo-vue';

window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.axios.withCredentials = true;

configureEcho({ broadcaster: 'reverb' });
