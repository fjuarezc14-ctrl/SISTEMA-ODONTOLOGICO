const fs = require('fs');
const files = [
    'caja.php', 'dashboard.php', 'pacientes.php', 
    'pacientes_detalles.php', 'paciente_detalle.php', 
    'presupuestos.php', 'presupuesto_detalle.php'
];
files.forEach(file => {
    if (fs.existsSync(file)) {
        let content = fs.readFileSync(file, 'utf8');
        let newContent = content.replace(/class="flex-1 overflow-y-auto p-8/g, 'class="flex-1 overflow-y-auto p-4 md:p-8');
        if (content !== newContent) {
            fs.writeFileSync(file, newContent, 'utf8');
            console.log('Updated ' + file);
        }
    }
});
