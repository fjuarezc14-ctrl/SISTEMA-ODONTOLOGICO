import re
import sys

file_path = "paciente_detalle.php"

with open(file_path, "r", encoding="utf-8") as f:
    content = f.read()

# 1. HTML Content for the new section
html_section = """
                    <!-- ARCHIVOS CLINICOS -->
                    <div id="seccion_archivos" class="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden mt-6">
                        <div class="p-6 border-b border-slate-100 flex flex-wrap justify-between items-center bg-slate-50/50 gap-4">
                            <div>
                                <h3 class="text-lg font-black text-slate-800 uppercase tracking-widest flex items-center gap-2">
                                    <i data-lucide="folder-open" class="w-5 h-5 text-indigo-600"></i> Archivos Clínicos
                                </h3>
                                <p class="text-sm text-slate-500 font-medium mt-1">Radiografías, fotos intraorales y documentos.</p>
                            </div>
                            <div class="flex gap-2 w-full md:w-auto">
                                <button onclick="abrirModalSubirArchivo()" class="flex-1 md:flex-none bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2.5 px-5 rounded-xl shadow-md transition flex items-center justify-center gap-2 text-xs uppercase tracking-wider">
                                    <i data-lucide="upload-cloud" class="w-4 h-4"></i> Subir Archivo
                                </button>
                            </div>
                        </div>
                        <div class="p-6">
                            <div class="flex gap-2 mb-6 overflow-x-auto pb-2" id="archivos_filters">
                                <button onclick="filtrarArchivos('Todos')" class="btn-filter px-4 py-2 text-sm font-bold rounded-lg bg-indigo-50 text-indigo-700 shadow-sm border border-indigo-100 whitespace-nowrap">Todos</button>
                                <button onclick="filtrarArchivos('Radiografía')" class="btn-filter px-4 py-2 text-sm font-bold rounded-lg text-slate-500 hover:text-slate-800 transition whitespace-nowrap">Radiografías</button>
                                <button onclick="filtrarArchivos('Foto Intraoral')" class="btn-filter px-4 py-2 text-sm font-bold rounded-lg text-slate-500 hover:text-slate-800 transition whitespace-nowrap">Fotos Intraorales</button>
                                <button onclick="filtrarArchivos('Documento')" class="btn-filter px-4 py-2 text-sm font-bold rounded-lg text-slate-500 hover:text-slate-800 transition whitespace-nowrap">Documentos</button>
                            </div>
                            
                            <div id="listaArchivosGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                <!-- Cards dinámicas aquí -->
                            </div>
                        </div>
                    </div>
"""

# Insert before </main>
if "</main>" in content:
    content = content.replace("</main>", html_section + "\n    </main>", 1)
else:
    print("Could not find </main>")
    sys.exit(1)

# 2. Modals and JavaScript
js_content = """
    <!-- Modal Subir Archivo -->
    <div id="modalSubirArchivo" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-[60] hidden items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md transform transition-transform duration-300 overflow-hidden">
            <div class="p-5 border-b border-slate-100 bg-slate-50 flex justify-between items-center">
                <h3 class="font-bold text-slate-800 flex items-center gap-2">
                    <i data-lucide="upload-cloud" class="w-5 h-5 text-indigo-600"></i> Subir Archivo
                </h3>
                <button onclick="cerrarModalSubirArchivo()" class="text-slate-400 hover:text-red-500 transition">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            <form id="formSubirArchivo" class="p-6 space-y-4">
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Tipo de Archivo</label>
                    <select id="archivoTipo" required class="w-full border-2 border-slate-200 rounded-xl p-2.5 text-sm font-bold text-slate-700 outline-none focus:border-indigo-500">
                        <option value="Radiografía">Radiografía</option>
                        <option value="Foto Intraoral">Foto Intraoral</option>
                        <option value="Documento">Documento (PDF)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Título / Descripción</label>
                    <input type="text" id="archivoDescripcion" required placeholder="Ej: Panorámica inicial..." class="w-full border-2 border-slate-200 rounded-xl p-2.5 text-sm text-slate-700 outline-none focus:border-indigo-500">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Seleccionar Archivo (Max 10MB)</label>
                    <input type="file" id="archivoFile" required accept=".jpg,.jpeg,.png,.pdf" class="w-full text-sm text-slate-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-bold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 transition cursor-pointer border-2 border-slate-200 rounded-xl">
                </div>
                <div class="pt-4 flex gap-3 justify-end">
                    <button type="button" onclick="cerrarModalSubirArchivo()" class="px-5 py-2.5 rounded-xl font-bold text-slate-600 hover:bg-slate-200 transition text-sm">Cancelar</button>
                    <button type="submit" class="px-5 py-2.5 rounded-xl font-bold text-white bg-indigo-600 hover:bg-indigo-700 shadow-md transition text-sm flex items-center gap-2">
                        <span id="textoBtnSubir">Guardar</span>
                        <div id="loaderBtnSubir" class="hidden w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Lightbox Visor de Imágenes -->
    <div id="lightboxVisor" class="fixed inset-0 bg-black/95 z-[70] hidden items-center justify-center p-4">
        <button onclick="cerrarLightbox()" class="absolute top-6 right-6 text-white/50 hover:text-white transition bg-black/50 p-2 rounded-full backdrop-blur-sm">
            <i data-lucide="x" class="w-6 h-6"></i>
        </button>
        <div class="absolute bottom-6 left-6 text-white/80">
            <h3 id="lightboxTitulo" class="font-bold text-lg"></h3>
            <p id="lightboxFecha" class="text-xs"></p>
        </div>
        <img id="lightboxImg" src="" class="max-w-full max-h-full object-contain rounded-lg shadow-2xl transition-transform duration-300" style="transform: scale(1);">
        
        <!-- Controles de Zoom -->
        <div class="absolute bottom-6 right-6 flex gap-2">
            <button onclick="zoomImg(-0.2)" class="w-10 h-10 bg-white/10 hover:bg-white/20 text-white rounded-full flex items-center justify-center backdrop-blur-sm transition">
                <i data-lucide="zoom-out" class="w-5 h-5"></i>
            </button>
            <button onclick="zoomImg(0.2)" class="w-10 h-10 bg-white/10 hover:bg-white/20 text-white rounded-full flex items-center justify-center backdrop-blur-sm transition">
                <i data-lucide="zoom-in" class="w-5 h-5"></i>
            </button>
            <button onclick="zoomImg(0, true)" class="w-10 h-10 bg-white/10 hover:bg-white/20 text-white rounded-full flex items-center justify-center backdrop-blur-sm transition">
                <i data-lucide="maximize" class="w-4 h-4"></i>
            </button>
        </div>
    </div>

    <script>
        let currentZoom = 1;
        let listaArchivosGlobal = [];
        let filtroActual = 'Todos';

        function abrirModalSubirArchivo() {
            document.getElementById('formSubirArchivo').reset();
            const m = document.getElementById('modalSubirArchivo');
            m.classList.remove('hidden');
            m.classList.add('flex');
        }

        function cerrarModalSubirArchivo() {
            const m = document.getElementById('modalSubirArchivo');
            m.classList.add('hidden');
            m.classList.remove('flex');
        }

        document.getElementById('formSubirArchivo').addEventListener('submit', async function(e) {
            e.preventDefault();
            const btnSubir = document.getElementById('textoBtnSubir');
            const loader = document.getElementById('loaderBtnSubir');
            
            btnSubir.classList.add('hidden');
            loader.classList.remove('hidden');

            const fileInput = document.getElementById('archivoFile');
            const file = fileInput.files[0];
            if (!file) return;

            const formData = new FormData();
            formData.append('paciente_id', pacienteId);
            formData.append('tipo', document.getElementById('archivoTipo').value);
            formData.append('descripcion', document.getElementById('archivoDescripcion').value);
            formData.append('archivo', file);

            try {
                const res = await fetch('ajax_archivos.php', { method: 'POST', body: formData });
                const data = await res.json();
                if (data.success) {
                    cerrarModalSubirArchivo();
                    cargarListaArchivos();
                } else {
                    alert('Error: ' + data.error);
                }
            } catch (err) {
                alert('Error de conexión');
            } finally {
                btnSubir.classList.remove('hidden');
                loader.classList.add('hidden');
            }
        });

        async function cargarListaArchivos() {
            try {
                const res = await fetch('ajax_archivos.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ accion: 'listar', paciente_id: pacienteId })
                });
                const data = await res.json();
                if (data.success) {
                    listaArchivosGlobal = data.archivos;
                    renderizarArchivos();
                }
            } catch (err) { console.error('Error cargando archivos:', err); }
        }

        function renderizarArchivos() {
            const grid = document.getElementById('listaArchivosGrid');
            grid.innerHTML = '';
            
            const filtrados = filtroActual === 'Todos' 
                ? listaArchivosGlobal 
                : listaArchivosGlobal.filter(a => a.tipo === filtroActual);

            if (filtrados.length === 0) {
                grid.innerHTML = `<div class="col-span-full py-10 text-center text-slate-400 font-medium text-sm">No hay archivos para mostrar en esta categoría.</div>`;
                return;
            }

            filtrados.forEach(a => {
                const isPdf = a.ruta_archivo.toLowerCase().endsWith('.pdf');
                const thumb = isPdf ? '' : `<img src="${a.ruta_archivo}" class="w-full h-full object-cover opacity-80 group-hover:scale-105 transition-transform duration-500">`;
                const icon = a.tipo === 'Radiografía' ? 'image' : (a.tipo === 'Foto Intraoral' ? 'camera' : 'file-text');
                
                const formatSize = (bytes) => (bytes / (1024*1024)).toFixed(2) + ' MB';
                const formatDate = (f) => f ? f.split(' ')[0] : '';

                let html = `
                <div class="group border border-slate-200 rounded-xl overflow-hidden hover:shadow-lg transition-shadow bg-white flex flex-col relative">
                    <button onclick="eliminarArchivo(${a.id})" class="absolute top-2 right-2 w-8 h-8 bg-white text-red-500 rounded-full shadow-md flex items-center justify-center opacity-0 group-hover:opacity-100 hover:bg-red-50 transition z-10" title="Eliminar">
                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                    </button>
                    <div class="relative h-40 ${isPdf ? 'bg-slate-50' : 'bg-slate-900'} flex items-center justify-center overflow-hidden">
                        ${isPdf ? `<div class="w-16 h-16 bg-red-100 rounded-2xl flex items-center justify-center text-red-500 mb-2 shadow-sm"><i data-lucide="file-text" class="w-8 h-8"></i></div><span class="absolute bottom-4 text-xs font-bold text-slate-400 uppercase tracking-widest">Documento PDF</span>` : thumb}
                        
                        <div class="absolute top-3 left-3 ${isPdf ? 'bg-white/80 border text-slate-600' : 'bg-black/60 text-white'} backdrop-blur-sm text-[10px] font-bold px-2 py-1 rounded uppercase tracking-wider flex items-center gap-1">
                            <i data-lucide="${icon}" class="w-3 h-3"></i> ${a.tipo}
                        </div>
                        <div class="absolute inset-0 ${isPdf ? 'bg-white/40' : 'bg-black/40'} opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-3">
                            ${!isPdf ? `<button onclick="abrirLightbox('${a.ruta_archivo}', '${a.descripcion}')" class="w-10 h-10 bg-white rounded-full flex items-center justify-center text-slate-800 hover:text-indigo-600 hover:scale-110 shadow-md transition"><i data-lucide="eye" class="w-5 h-5"></i></button>` : ''}
                            <a href="${a.ruta_archivo}" download="${a.nombre_archivo}" class="w-10 h-10 bg-white rounded-full flex items-center justify-center text-slate-800 hover:text-indigo-600 hover:scale-110 shadow-md transition"><i data-lucide="download" class="w-5 h-5"></i></a>
                        </div>
                    </div>
                    <div class="p-4 flex-1 flex flex-col justify-between">
                        <div>
                            <h4 class="font-bold text-slate-800 text-sm truncate" title="${a.descripcion}">${a.descripcion}</h4>
                            <p class="text-[10px] text-slate-400 mt-1 truncate" title="${a.nombre_archivo}">${a.nombre_archivo}</p>
                        </div>
                        <div class="flex justify-between items-center mt-4 pt-4 border-t border-slate-100">
                            <span class="text-[10px] font-bold text-slate-400">${formatDate(a.fecha_subida)}</span>
                            <span class="text-[10px] font-bold text-slate-400">${formatSize(a.tamano)}</span>
                        </div>
                    </div>
                </div>`;
                grid.innerHTML += html;
            });
            lucide.createIcons();
        }

        function filtrarArchivos(tipo) {
            filtroActual = tipo;
            document.querySelectorAll('.btn-filter').forEach(b => {
                b.className = "btn-filter px-4 py-2 text-sm font-bold rounded-lg text-slate-500 hover:text-slate-800 transition whitespace-nowrap";
            });
            event.target.className = "btn-filter px-4 py-2 text-sm font-bold rounded-lg bg-indigo-50 text-indigo-700 shadow-sm border border-indigo-100 whitespace-nowrap";
            renderizarArchivos();
        }

        async function eliminarArchivo(id) {
            if (!confirm('¿Está seguro de eliminar este archivo permanentemente?')) return;
            try {
                const res = await fetch('ajax_archivos.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ accion: 'eliminar', archivo_id: id })
                });
                const data = await res.json();
                if (data.success) {
                    cargarListaArchivos();
                } else {
                    alert('Error: ' + data.error);
                }
            } catch(e) { alert('Error de conexión'); }
        }

        function abrirLightbox(ruta, desc) {
            document.getElementById('lightboxImg').src = ruta;
            document.getElementById('lightboxTitulo').innerText = desc;
            currentZoom = 1;
            document.getElementById('lightboxImg').style.transform = `scale(1)`;
            const m = document.getElementById('lightboxVisor');
            m.classList.remove('hidden');
            m.classList.add('flex');
        }

        function cerrarLightbox() {
            const m = document.getElementById('lightboxVisor');
            m.classList.add('hidden');
            m.classList.remove('flex');
            document.getElementById('lightboxImg').src = '';
        }

        function zoomImg(delta, reset=false) {
            if (reset) currentZoom = 1;
            else currentZoom = Math.max(0.5, Math.min(4, currentZoom + delta));
            document.getElementById('lightboxImg').style.transform = `scale(${currentZoom})`;
        }

        // Cargar lista al iniciar
        document.addEventListener('DOMContentLoaded', () => {
            cargarListaArchivos();
        });
    </script>
"""

if "</body>" in content:
    content = content.replace("</body>", js_content + "\n</body>", 1)
else:
    print("Could not find </body>")
    sys.exit(1)

with open(file_path, "w", encoding="utf-8") as f:
    f.write(content)

print("Modification complete.")
