/* 
 * Copyright (c) 2018 Bardur Thomsen <https://github.com/bardurt>.
 * All rights reserved. This program and the accompanying materials
 * are made available under the terms of the Eclipse Public License v1.0
 * which accompanies this distribution, and is available at
 * http://www.eclipse.org/legal/epl-v10.html
 *
 * Contributors:
 *    Bardur Thomsen <https://github.com/bardurt> - initial API and implementation and/or initial documentation
 */

/*
 * Class which represents a simple textbox 
 */

function TextBox() {
    "use strict";
    this.text = "";
    this.rect = new Rect();
    this.touching = false;
}

/**
 * Set the dimension of the rectangle
 * @param {type} x position in canvas
 * @param {type} y position in canvas
 * @param {type} width of rectangle
 * @param {type} height of rectangle
 * @returns {undefined}
 */
TextBox.prototype.setDimens = function (x, y, width, height) {
    "use strict";
    this.rect.x = x;
    this.rect.y = y;
    this.rect.width = width;
    this.rect.height = height;
    this.text = "";
    this.label = "";
};

/**
 * Method to set the text which should be displayed in the textbox
 * @param {type} text string to draw
 * @returns {undefined}
 */
TextBox.prototype.setText = function (text) {
    "use strict";
    this.text = text;
};

TextBox.prototype.setLabel = function (label) {
    "use strict";
    this.label = label;
};

/**
 * Draw a text lable on the textbox
 * @param {type} context
 * @returns {undefined}
 */
TextBox.prototype.drawLabel = function (context) {
    "use strict";
    this.rect.outline(context, "#000000");

    context.beginPath();

    context.textAlign = "center";
    context.fillStyle = "#9a9a9a";
    context.font = "11px Arial";

    context.fillText(this.label,
            this.rect.x + this.rect.width / 2,
            this.rect.y + this.rect.height - 4);

    context.stroke();

    context.restore();

};

/**
 * Draw a text on textbox displaying tooth number when empty and dental code when entered
 * @param {type} context canvas to draw on
 * @param {type} color color of the text to draw
 * @returns {void}
 */
TextBox.prototype.drawText = function (context, color) {
    "use strict";
    context.save();

    var hasText = (this.text !== null && String(this.text).trim() !== "");
    var isTouching = this.touching;

    // Dibujar casilla redondeada médica
    if (context.roundRect) {
        context.beginPath();
        context.roundRect(this.rect.x, this.rect.y, this.rect.width, this.rect.height, 4);
    } else {
        context.beginPath();
        context.rect(this.rect.x, this.rect.y, this.rect.width, this.rect.height);
    }

    if (hasText) {
        // Código ingresado (ej: EXT, RES, END)
        context.fillStyle = "#dbeafe";
        context.fill();
        context.lineWidth = 1.5;
        context.strokeStyle = "#2563eb";
        context.stroke();

        context.beginPath();
        context.textAlign = "center";
        context.textBaseline = "middle";
        context.fillStyle = "#1e40af";
        context.font = "bold 13px 'Inter', 'Segoe UI', sans-serif";
        context.fillText(String(this.text).toUpperCase(), this.rect.x + this.rect.width / 2, this.rect.y + this.rect.height / 2);

    } else if (isTouching) {
        // Hover sobre casilla vacía
        context.fillStyle = "#e0e7ff";
        context.fill();
        context.lineWidth = 1.5;
        context.strokeStyle = "#4f46e5";
        context.stroke();

        context.beginPath();
        context.textAlign = "center";
        context.textBaseline = "middle";
        context.fillStyle = "#3730a3";
        context.font = "bold 13px 'Inter', 'Segoe UI', sans-serif";
        context.fillText(String(this.label || ""), this.rect.x + this.rect.width / 2, this.rect.y + this.rect.height / 2);

    } else {
        // Número de Diente FDI siempre claro, visible y nítido
        context.fillStyle = "#f8fafc";
        context.fill();
        context.lineWidth = 1;
        context.strokeStyle = "#cbd5e1";
        context.stroke();

        context.beginPath();
        context.textAlign = "center";
        context.textBaseline = "middle";
        context.fillStyle = "#1e293b";
        context.font = "bold 13px 'Inter', 'Segoe UI', sans-serif";
        context.fillText(String(this.label || ""), this.rect.x + this.rect.width / 2, this.rect.y + this.rect.height / 2);
    }

    context.restore();
};

TextBox.prototype.drawLabel = function (context) {
    "use strict";
    // Delegate to drawText to ensure unified rendering
    this.drawText(context, "#1e293b");
};

/**
 * Method to draw the textbox onto a canvas
 * @param {type} context the canvas to draw on
 * @param {type} color the color of the text
 * @returns {undefined}
 */
TextBox.prototype.render = function (context, color) {
    "use strict";
    this.drawText(context, color);
};

TextBox.prototype.setNote = function (note) {
    "use strict";
    this.text = note ? note.toUpperCase() : "";
};
