/**
 * Catalog & Pricing Manager for Odontogram Integration
 * Conecta los hallazgos y procedimientos del Odontograma con el tarifario de la clínica.
 */

(function (window) {
    "use strict";

    // Catálogo configurable de Ítems / Servicios Odontológicos
    const PricingCatalog = {
        // Mapeo por ID de Constante del Odontograma
        damages: {
            "1":  { item_id: "SERV-001", nombre: "Curación con Resina", precio: 80.00, categoria: "Restauración" },
            "2":  { item_id: "SERV-002", nombre: "Corona Definitiva", precio: 350.00, categoria: "Prótesis" },
            "3":  { item_id: "SERV-003", nombre: "Corona Temporal / Provisional", precio: 90.00, categoria: "Prótesis" },
            "4":  { item_id: "SERV-004", nombre: "Extracción Dental Simple", precio: 60.00, categoria: "Cirugía" },
            "5":  { item_id: "SERV-005", nombre: "Reconstrucción por Fractura Dental", precio: 80.00, categoria: "Restauración" },
            "6":  { item_id: "SERV-006", nombre: "Implante Dental", precio: 800.00, categoria: "Implantes" },
            "8":  { item_id: "SERV-008", nombre: "Cierre de Diastema", precio: 100.00, categoria: "Estética" },
            "9":  { item_id: "SERV-009", nombre: "Tratamiento por Diente Extruido", precio: 110.00, categoria: "Ortodoncia" },
            "11": { item_id: "SERV-011", nombre: "Restauración / Obturación", precio: 80.00, categoria: "Restauración" },
            "12": { item_id: "SERV-012", nombre: "Prótesis Removible (por pieza/arcada)", precio: 400.00, categoria: "Prótesis" },
            "13": { item_id: "SERV-013", nombre: "Corrección de Migración", precio: 120.00, categoria: "Ortodoncia" },
            "14": { item_id: "SERV-014", nombre: "Ajuste de Giroversión", precio: 100.00, categoria: "Ortodoncia" },
            "16": { item_id: "SERV-016", nombre: "Extracción de Remanente Radicular", precio: 75.00, categoria: "Cirugía" },
            "20": { item_id: "SERV-020", nombre: "Tratamiento Pulpar / Endodoncia", precio: 200.00, categoria: "Endodoncia" },
            "23": { item_id: "SERV-023", nombre: "Aparato de Ortodoncia Removible", precio: 200.00, categoria: "Ortodoncia" },
            "28": { item_id: "SERV-028", nombre: "Tratamiento de Conducto Pulpar", precio: 250.00, categoria: "Endodoncia" },
            "29": { item_id: "SERV-029", nombre: "Prótesis Total Dental", precio: 600.00, categoria: "Prótesis" },
            "30": { item_id: "SERV-030", nombre: "Perno Muñón / Fibra", precio: 100.00, categoria: "Restauración" },
            "31": { item_id: "SERV-031", nombre: "Tratamiento Edentulismo Total", precio: 600.00, categoria: "Prótesis" },
            "32": { item_id: "SERV-032", nombre: "Ortodoncia Fija (Brackets)", precio: 2500.00, categoria: "Ortodoncia" },
            "34": { item_id: "SERV-034", nombre: "Prótesis Fija Puentes Dentales", precio: 500.00, categoria: "Prótesis" },
            "37": { item_id: "SERV-037", nombre: "Férula Miorrelajante por Desgaste", precio: 250.00, categoria: "Oclusión" },
            "39": { item_id: "SERV-039", nombre: "Sellante Dental Fosas y Fisuras", precio: 40.00, categoria: "Prevención" }
        },

        // Mapeo por Siglas o Notas (Casillas de 3 letras)
        notes: {
            "EXT": { item_id: "SERV-004", nombre: "Extracción Dental Simple", precio: 60.00 },
            "END": { item_id: "SERV-020", nombre: "Endodoncia / Tratamiento Conducto", precio: 250.00 },
            "IMP": { item_id: "SERV-006", nombre: "Implante Dental", precio: 800.00 },
            "RES": { item_id: "SERV-011", nombre: "Curación con Resina", precio: 80.00 },
            "COR": { item_id: "SERV-002", nombre: "Corona Porcelana", precio: 350.00 },
            "SEL": { item_id: "SERV-039", nombre: "Sellante Dental", precio: 40.00 }
        },

        /**
         * Obtener item y precio asociado a una entrada del odontograma
         */
        getItemInfo: function (entry) {
            let noteUpper = (entry.note || "").trim().toUpperCase();
            if (noteUpper && this.notes[noteUpper]) {
                return this.notes[noteUpper];
            }

            let dmgKey = String(entry.damage || entry.estado || "");
            if (dmgKey && this.damages[dmgKey]) {
                return this.damages[dmgKey];
            }

            // Fallback genérico si es un procedimiento sin precio específico
            return {
                item_id: "SERV-GEN",
                nombre: entry.estado_nombre || entry.cara_nombre || "Procedimiento Dental",
                precio: 40.00
            };
        },

        /**
         * Recalcular el presupuesto total a partir de engine.getData()
         * Agrupa CARIES por diente para aplicar el contador de caras (Simple, Compuesta, Compleja)
         * El resto de tratamientos se cobra de forma normal 1 a 1 por pieza/procedimiento
         */
        calculateBudget: function (dataList) {
            let items = [];
            let total = 0;

            if (!Array.isArray(dataList)) return { items: [], total: 0 };

            // 1. Separar Restauraciones (Caries, Fractura, Defectuosa) por pieza para aplicar regla de caras
            let cariesPorDiente = {};
            let otrosProcedimientos = [];

            dataList.forEach((entry) => {
                let dmg = String(entry.damage || entry.estado || "");
                if (dmg === "1" || dmg === "caries" || dmg === "5" || dmg === "fractura" || dmg === "11" || dmg === "restauracion_defectuosa" || dmg === "resina") {
                    let tooth = entry.diente_numero || entry.tooth || "0";
                    if (!cariesPorDiente[tooth]) {
                        cariesPorDiente[tooth] = {
                            tooth: tooth,
                            dentureType: entry.tipo_dentadura || "Adulto",
                            note: entry.note || "",
                            caras: []
                        };
                    }
                    let nombreCara = entry.cara_nombre || entry.surface || "Cara";
                    cariesPorDiente[tooth].caras.push(nombreCara);
                } else {
                    otrosProcedimientos.push(entry);
                }
            });

            let itemIndex = 1;

            // 2. Procesar Caries con contador de caras
            Object.keys(cariesPorDiente).forEach((tooth) => {
                let grupo = cariesPorDiente[tooth];
                let carasUnicas = Array.from(new Set(grupo.caras));
                let cantCaras = carasUnicas.length;
                let info;

                if (cantCaras === 1) {
                    info = {
                        item_id: "SERV-001A",
                        nombre: "Curación Simple (" + carasUnicas[0] + ")",
                        precio: 80.00
                    };
                } else if (cantCaras === 2) {
                    info = {
                        item_id: "SERV-001B",
                        nombre: "Curación Compuesta (" + carasUnicas.join(", ") + ")",
                        precio: 120.00
                    };
                } else {
                    info = {
                        item_id: "SERV-001C",
                        nombre: "Curación Compleja (" + cantCaras + " caras: " + carasUnicas.join(", ") + ")",
                        precio: 150.00
                    };
                }

                total += info.precio;
                items.push({
                    index: itemIndex++,
                    diente: tooth,
                    cara: carasUnicas.join(" - "),
                    procedimiento: info.nombre,
                    item_id: info.item_id,
                    nota: grupo.note,
                    dentadura: grupo.dentureType,
                    precioUnitario: info.precio,
                    subtotal: info.precio
                });
            });

            // 3. Procesar el resto de tratamientos de forma normal
            otrosProcedimientos.forEach((entry) => {
                let info = this.getItemInfo(entry);
                let subtotal = info.precio;
                total += subtotal;

                items.push({
                    index: itemIndex++,
                    diente: entry.diente_numero || entry.tooth || "N/A",
                    cara: entry.cara_nombre || entry.cara_afectada || "General",
                    procedimiento: info.nombre,
                    item_id: info.item_id,
                    nota: entry.note || "",
                    dentadura: entry.tipo_dentadura || "Adulto",
                    precioUnitario: info.precio,
                    subtotal: subtotal
                });
            });

            return {
                items: items,
                total: total
            };
        },

        /**
         * Renderizar tabla HTML de presupuesto en un contenedor DOM
         */
        renderBudgetTable: function (containerId, budgetData) {
            let container = typeof containerId === 'string' ? document.getElementById(containerId) : containerId;
            if (!container) return;

            if (!budgetData || budgetData.items.length === 0) {
                container.innerHTML = `
                    <div style="padding: 24px; text-align: center; color: #64748b; background: #f8fafc; border-radius: 12px; border: 1px dashed #cbd5e1;">
                        <svg style="width: 36px; height: 36px; margin: 0 auto 8px; stroke: #94a3b8;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <p style="margin: 0; font-size: 14px; font-weight: 500;">No hay tratamientos ni hallazgos registrados</p>
                        <span style="font-size: 12px; color: #94a3b8;">Haz clic en los botones o piezas dentales para generar la cotización.</span>
                    </div>
                `;
                return;
            }

            let rowsHtml = budgetData.items.map(item => `
                <tr style="border-bottom: 1px solid #f1f5f9; transition: background 0.15s ease;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='transparent'">
                    <td style="padding: 10px 12px; font-weight: 700; color: #1e293b; text-align: center;">
                        <span style="background: #e0f2fe; color: #0369a1; padding: 2px 8px; border-radius: 12px; font-size: 12px;">#${item.diente}</span>
                    </td>
                    <td style="padding: 10px 12px; color: #475569; font-size: 13px;">${item.cara}</td>
                    <td style="padding: 10px 12px; font-weight: 600; color: #0f172a; font-size: 13px;">
                        ${item.procedimiento}
                        ${item.nota ? `<span style="font-size: 11px; background: #fef3c7; color: #b45309; padding: 1px 6px; border-radius: 4px; margin-left: 6px;">[${item.nota}]</span>` : ''}
                    </td>
                    <td style="padding: 10px 12px; color: #64748b; font-size: 12px;">${item.item_id}</td>
                    <td style="padding: 10px 12px; font-weight: 700; color: #059669; text-align: right; font-size: 13px;">
                        S/ ${item.subtotal.toFixed(2)}
                    </td>
                </tr>
            `).join('');

            container.innerHTML = `
                <div style="background: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.03);">
                    <div style="padding: 14px 16px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
                        <h4 style="margin: 0; font-size: 15px; font-weight: 700; color: #0f172a; display: flex; align-items: center; gap: 8px;">
                            <span>📑 Plan de Tratamiento & Presupuesto</span>
                            <span style="background: #3b82f6; color: #ffffff; font-size: 11px; padding: 2px 8px; border-radius: 10px;">${budgetData.items.length} ítems</span>
                        </h4>
                    </div>
                    <div style="max-height: 380px; overflow-y: auto;">
                        <table style="width: 100%; border-collapse: collapse; text-align: left;">
                            <thead>
                                <tr style="background: #f1f5f9; color: #475569; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;">
                                    <th style="padding: 8px 12px; text-align: center;">Diente</th>
                                    <th style="padding: 8px 12px;">Zona / Cara</th>
                                    <th style="padding: 8px 12px;">Procedimiento</th>
                                    <th style="padding: 8px 12px;">Código</th>
                                    <th style="padding: 8px 12px; text-align: right;">Precio</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${rowsHtml}
                            </tbody>
                        </table>
                    </div>
                    <div style="padding: 14px 16px; background: #ecfdf5; border-top: 1px solid #a7f3d0; display: flex; justify-content: space-between; align-items: center;">
                        <span style="font-size: 14px; font-weight: 600; color: #065f46;">Total Estimado del Tratamiento:</span>
                        <span style="font-size: 20px; font-weight: 800; color: #047857;">S/ ${budgetData.total.toFixed(2)}</span>
                    </div>
                </div>
            `;
        }
    };

    window.PricingCatalog = PricingCatalog;
})(window);
