import Alpine from "alpinejs";
import intersect from "@alpinejs/intersect";
import axios from "axios";

window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

window.Alpine = Alpine
Alpine.plugin(intersect);
Alpine.start()
