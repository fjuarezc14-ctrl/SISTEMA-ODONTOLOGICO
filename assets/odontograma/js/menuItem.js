
/**
 * Base class for tooth
 * @returns {MenuItem}
 */
function MenuItem() {
    "use strict";
    this.active = false;
    this.id = 0;
    this.tooth = true;
    this.surfaces = 0;
    this.highlight = false;
    this.rect = new Rect();
    this.textBox = new TextBox();
    this.spacer = 20; // spacer to seperate tooth from surfaces
    this.touching = false;
    this.address = 0;
    this.normalY = null;
    this.highY = null;
    this.blocked = false;
    this.constants = null;

}


MenuItem.prototype.setUp = function (x, y, width, height) {
    "use strict";

    this.rect.x = x;
    this.rect.y = y;
    this.rect.width = width;
    this.rect.height = height;

    this.textBox.rect.x = x;
    this.textBox.rect.y = y;
    this.textBox.rect.width = width;
    this.textBox.rect.height = height;

};


/**
 * Method to render a Tooth on the screen with all its states
 * @param {type} context the canvas to draw on
 * @param {type} settings app settings
 * @param {type} constants application constants
 * @returns {undefined}
 */
MenuItem.prototype.render = function (context, settings, constants) {
    "use strict";

    if (this.active) {
        this.renderStateActive(context);
    } else {
        this.renderStateNormal(context);
    }

    if(this.highlight) {
        this.renderStateFocus(context);
    } 

    this.renderLabel(context);
};


function drawRoundedRect(context, x, y, width, height, radius) {
    if (context.roundRect) {
        context.beginPath();
        context.roundRect(x, y, width, height, radius);
    } else {
        context.beginPath();
        context.moveTo(x + radius, y);
        context.lineTo(x + width - radius, y);
        context.quadraticCurveTo(x + width, y, x + width, y + radius);
        context.lineTo(x + width, y + height - radius);
        context.quadraticCurveTo(x + width, y + height, x + width - radius, y + height);
        context.lineTo(x + radius, y + height);
        context.quadraticCurveTo(x, y + height, x, y + height - radius);
        context.lineTo(x, y + radius);
        context.quadraticCurveTo(x, y, x + radius, y);
        context.closePath();
    }
}

MenuItem.prototype.getCategoryColors = function () {
    var id = Number(this.id);
    // Patologías / Hallazgos (🔴 Red)
    if ([1, 5, 37, 19, 38, 20, 9, 21, 22].indexOf(id) !== -1) {
        return {
            normalBg: "#fff1f2", normalBorder: "#fecdd3", normalDot: "#e11d48", normalText: "#9f1239",
            activeBg: "#e11d48", activeBorder: "#be123c", activeDot: "#ffffff", activeText: "#ffffff",
            focusBg: "#ffe4e6", focusBorder: "#fda4af", focusText: "#881337"
        };
    }
    // Restauraciones & Prótesis (🟦 Blue)
    if ([11, 2, 3, 12, 34, 29, 6, 30].indexOf(id) !== -1) {
        return {
            normalBg: "#eff6ff", normalBorder: "#bfdbfe", normalDot: "#2563eb", normalText: "#1e40af",
            activeBg: "#2563eb", activeBorder: "#1d4ed8", activeDot: "#ffffff", activeText: "#ffffff",
            focusBg: "#dbeafe", focusBorder: "#93c5fd", focusText: "#1e3a8a"
        };
    }
    // Ortodoncia & Posición (🟪 Purple)
    if ([32, 23, 13, 14, 25, 8].indexOf(id) !== -1) {
        return {
            normalBg: "#faf5ff", normalBorder: "#e9d5ff", normalDot: "#9333ea", normalText: "#6b21a8",
            activeBg: "#9333ea", activeBorder: "#7e22ce", activeDot: "#ffffff", activeText: "#ffffff",
            focusBg: "#f3e8ff", focusBorder: "#d8b4fe", focusText: "#581c87"
        };
    }
    // Evolución & Quirúrgico (🟩 Green)
    if ([4, 16, 28, 27, 24, 31, 10, 17, 18, 15, 39].indexOf(id) !== -1) {
        return {
            normalBg: "#f0fdf4", normalBorder: "#bbf7d0", normalDot: "#16a34a", normalText: "#166534",
            activeBg: "#16a34a", activeBorder: "#15803d", activeDot: "#ffffff", activeText: "#ffffff",
            focusBg: "#dcfce7", focusBorder: "#86efac", focusText: "#14532d"
        };
    }

    // Default neutral slate
    return {
        normalBg: "#f8fafc", normalBorder: "#cbd5e1", normalDot: "#64748b", normalText: "#334155",
        activeBg: "#2563eb", activeBorder: "#1d4ed8", activeDot: "#ffffff", activeText: "#ffffff",
        focusBg: "#e0e7ff", focusBorder: "#6366f1", focusText: "#3730a3"
    };
};

MenuItem.prototype.renderStateNormal = function (context) {
    "use strict";
    var colors = this.getCategoryColors();
    context.save();
    drawRoundedRect(context, this.rect.x, this.rect.y, this.rect.width, this.rect.height, 5);
    context.fillStyle = colors.normalBg;
    context.fill();

    context.lineWidth = 1;
    context.strokeStyle = colors.normalBorder;
    context.stroke();

    // Punto de categoría
    context.beginPath();
    context.arc(this.rect.x + 8, this.rect.y + this.rect.height / 2, 3, 0, Math.PI * 2);
    context.fillStyle = colors.normalDot;
    context.fill();

    context.restore();
};

MenuItem.prototype.renderStateActive = function (context) {
    "use strict";
    var colors = this.getCategoryColors();
    context.save();
    drawRoundedRect(context, this.rect.x, this.rect.y, this.rect.width, this.rect.height, 5);
    context.fillStyle = colors.activeBg;
    context.fill();

    context.lineWidth = 1.5;
    context.strokeStyle = colors.activeBorder;
    context.stroke();

    // Punto blanco activo
    context.beginPath();
    context.arc(this.rect.x + 8, this.rect.y + this.rect.height / 2, 3.5, 0, Math.PI * 2);
    context.fillStyle = colors.activeDot;
    context.fill();

    context.restore();
};

MenuItem.prototype.renderStateFocus = function (context) {
    "use strict";
    var colors = this.getCategoryColors();
    context.save();
    drawRoundedRect(context, this.rect.x, this.rect.y, this.rect.width, this.rect.height, 5);
    context.fillStyle = colors.focusBg;
    context.fill();

    context.lineWidth = 1.5;
    context.strokeStyle = colors.focusBorder;
    context.stroke();

    // Punto de categoría en hover
    context.beginPath();
    context.arc(this.rect.x + 8, this.rect.y + this.rect.height / 2, 3, 0, Math.PI * 2);
    context.fillStyle = colors.normalDot;
    context.fill();

    context.restore();
};

MenuItem.prototype.renderLabel = function (context) {
    "use strict";
    var colors = this.getCategoryColors();
    context.save();
    context.textAlign = "center";
    context.textBaseline = "middle";

    if (this.active) {
        context.fillStyle = colors.activeText;
        context.font = "bold 11px 'Segoe UI', 'Inter', system-ui, sans-serif";
    } else if (this.highlight) {
        context.fillStyle = colors.focusText;
        context.font = "bold 11px 'Segoe UI', 'Inter', system-ui, sans-serif";
    } else {
        context.fillStyle = colors.normalText;
        context.font = "500 11px 'Segoe UI', 'Inter', system-ui, sans-serif";
    }

    var textX = this.rect.x + (this.rect.width / 2) + 3;
    context.fillText(this.textBox.text, textX, this.rect.y + this.rect.height / 2);

    context.restore();
};