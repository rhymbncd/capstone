/*
 * Bundles SweetAlert2 (JS + CSS) and exposes it as window.Swal for Blade
 * templates that call Swal.fire() from inline <script> rather than an
 * imported module — currently the student modules page. Replaces the CDN
 * <script> + <link> tags so nothing on the page loads from a third party.
 */
import Swal from 'sweetalert2';
import 'sweetalert2/dist/sweetalert2.min.css';

window.Swal = Swal;
