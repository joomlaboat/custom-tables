/**
 * CustomTables Joomla! 3.x/4.x/5.x/6.x Component and WordPress 6.x Plugin
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2026. All Rights Reserved
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/

//if (typeof globalThis.CustomTablesCSVImport === 'undefined') {
class CustomTablesCSVImport {


	constructor(canvasElementId, img, horizontalLines = null, verticalLines = null,
	            horizontalEditBox = "comes_horizontal_lines", verticalEditBox = "comes_vertical_lines") {

		if (!img)
			return;

		this.horizontalEditBox = horizontalEditBox;
		this.verticalEditBox = verticalEditBox;

		this.horizontalLines = horizontalLines;
		this.verticalLines = verticalLines;

		this.canvas = document.getElementById(canvasElementId);
		this.MIN_LINE_SPACING = 2; // pixels
		this.ctx = this.canvas.getContext("2d");
		this.draggingHLineIndex = -1;
		this.draggingVLineIndex = -1;

		this.draggingHLine = null;
		this.draggingVLine = null;

		this.canvas.width = img.width;
		this.canvas.height = img.height;

		// draw image
		this.ctx.drawImage(img, 0, 0);

		this.addLineCandidate = null;
		this.deleteLineCandidate = null;
		this.LEFT_ZONE = 70;     // pixels from left edge
		this.TOP_ZONE = 70;     // pixels from left edge
		this.BETWEEN_TOLERANCE = 10;

		this.tolerance = 5;

		this.HORIZONTAL = 1;
		this.VERTICAL = 2;
		this.img = img;
	}

	drawCanvas() {

		const ctx = this.ctx;
		ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
		ctx.drawImage(this.img, 0, 0);

		ctx.lineWidth = 2;
		ctx.setLineDash([10, 6]);
		ctx.strokeStyle = "white";
		ctx.globalCompositeOperation = "source-over";
		ctx.strokeStyle = "rgba(252,5,161,1)";

		ctx.beginPath();

// Horizontal lines
		for (let i = 0; i < this.horizontalLines.length; i++) {
			const line = this.horizontalLines[i];

			ctx.moveTo(line[0][0], line[0][1]);

			for (let j = 1; j < line.length; j++) {
				ctx.lineTo(line[j][0], line[j][1]);
			}
		}

// Vertical lines
		for (let i = 0; i < this.verticalLines.length; i++) {
			const line = this.verticalLines[i];

			ctx.moveTo(line[0][0], line[0][1]);

			for (let j = 1; j < line.length; j++) {
				ctx.lineTo(line[j][0], line[j][1]);
			}
		}

		ctx.stroke();

	}

	findHLineAtPosition(mouseX, mouseY) {

		let index = 0;
		for (let line of this.horizontalLines) {

			for (let i = 0; i < line.length - 1; i++) {

				let [x1, y1] = line[i];
				let [x2, y2] = line[i + 1];
				if (this.pointNearSegment(mouseX, mouseY, x1, y1, x2, y2, this.tolerance)) {
					return [index, line];
				}
			}
			index += 1;
		}
		return null;
	}

	findVLineAtPosition(mouseX, mouseY) {

		let index = 0;
		for (let line of this.verticalLines) {

			for (let i = 0; i < line.length - 1; i++) {

				let [x1, y1] = line[i];
				let [x2, y2] = line[i + 1];

				if (this.pointNearSegment(mouseX, mouseY, x1, y1, x2, y2, this.tolerance)) {
					return [index, line];
				}
			}
			index += 1;
		}
		return null;
	}

	pointNearSegment(px, py, x1, y1, x2, y2, tolerance) {

		const A = px - x1;
		const B = py - y1;
		const C = x2 - x1;
		const D = y2 - y1;

		const dot = A * C + B * D;
		const lenSq = C * C + D * D;

		let param = -1;
		if (lenSq !== 0) {
			param = dot / lenSq;
		}

		let xx, yy;

		if (param < 0) {
			xx = x1;
			yy = y1;
		} else if (param > 1) {
			xx = x2;
			yy = y2;
		} else {
			xx = x1 + param * C;
			yy = y1 + param * D;
		}

		const dx = px - xx;
		const dy = py - yy;

		return Math.sqrt(dx * dx + dy * dy) < tolerance;
	}

	defineEvents() {


		this.canvas.addEventListener("pointerdown", e => {
			const rect = this.canvas.getBoundingClientRect();
			const mouseX = e.clientX - rect.left;
			const mouseY = e.clientY - rect.top;

			// Prevent scrolling on mobile
			e.preventDefault();

			let line = this.findHLineAtPosition(mouseX, mouseY);
			if (line) {
				this.draggingHLine = line[1];
				this.lastMouseY = mouseY;
				this.draggingHLineIndex = line[0];
			}

			line = this.findVLineAtPosition(mouseX, mouseY);
			if (line) {
				this.draggingVLine = line[1];
				this.lastMouseX = mouseX;
				this.draggingVLineIndex = line[0];
			}

			// ADD LINE
			if (this.addLineCandidate) {

				if (this.addLineCandidate.type === this.HORIZONTAL) {
					if (mouseX < 30 && mouseY > this.addLineCandidate.y - 10 && mouseY < this.addLineCandidate.y + 10)
						this.addNewHorizontalLine();
				} else if (this.addLineCandidate.type === this.VERTICAL) {
					if (mouseY < 30 && mouseX > this.addLineCandidate.x - 10 && mouseX < this.addLineCandidate.x + 10)
						this.addNewVerticalLine();
				}
			}

			// DELETE LINE
			if (this.deleteLineCandidate) {
				if (mouseX < 30 && this.deleteLineCandidate.type === this.HORIZONTAL) {
					if (this.deleteLineCandidate.index === this.horizontalLines.length - 1)
						this.horizontalLines.pop();
					else
						this.horizontalLines.splice(this.deleteLineCandidate.index, 1);

					this.saveHorizontalLines();

				} else if (mouseY < 30 && this.deleteLineCandidate.type === this.VERTICAL) {
					if (this.deleteLineCandidate.index === this.verticalLines.length - 1)
						this.verticalLines.pop();
					else
						this.verticalLines.splice(this.deleteLineCandidate.index, 1);

					this.saveVerticalLines();
				}

				this.deleteLineCandidate = null;
				this.drawCanvas();
			}
		}, {passive: false});

		this.canvas.addEventListener("pointermove", e => {

			const rect = this.canvas.getBoundingClientRect();
			const mouseX = e.clientX - rect.left;
			const mouseY = e.clientY - rect.top;

			// dragging logic
			if (this.draggingHLine) {

				const deltaY = mouseY - this.lastMouseY;

				let closesPoint = null;
				let closesPointDistance = null;
				this.draggingHLine.forEach(point => {

					if (closesPoint === null || Math.abs(point[0] - mouseX) < closesPointDistance) {
						closesPoint = point;
						closesPointDistance = Math.abs(point[0] - mouseX);
					}

				});

				let pointY = closesPoint[1];
				let newY = pointY + deltaY;

				if (this.draggingHLineIndex !== -1) {
					const lineAbove = this.horizontalLines[this.draggingHLineIndex - 1];
					const lineBelow = this.horizontalLines[this.draggingHLineIndex + 1];

					if (lineAbove) {
						const maxUp = lineAbove[0][1] + this.MIN_LINE_SPACING;
						if (newY < maxUp) newY = maxUp;
					}

					if (lineBelow) {
						const maxDown = lineBelow[0][1] - this.MIN_LINE_SPACING;
						if (newY > maxDown) newY = maxDown;
					}
				}


				const shiftH = newY - pointY;


				this.draggingHLine.forEach(point => {
					point[1] += shiftH;
				});

				this.lastMouseY = newY;
				this.drawCanvas();
				return;
			}

			if (this.draggingVLine) {

				const deltaX = mouseX - this.lastMouseX;

				let closesPoint = null;
				let closesPointDistance = null;
				this.draggingVLine.forEach(point => {

					if (closesPoint === null || Math.abs(point[1] - mouseY) < closesPointDistance) {
						closesPoint = point;
						closesPointDistance = Math.abs(point[1] - mouseY);
					}

				});

				let pointX = closesPoint[0];
				let newX = pointX + deltaX;

				if (this.draggingVLineIndex !== -1) {
					const lineLeft = this.verticalLines[this.draggingVLineIndex - 1];
					const lineRight = this.verticalLines[this.draggingVLineIndex + 1];

					if (lineLeft) {
						const maxLeft = lineLeft[0][0] + this.MIN_LINE_SPACING;
						if (newX < maxLeft) newX = maxLeft;
					}

					if (lineRight) {
						const maxRight = lineRight[0][0] - this.MIN_LINE_SPACING;
						if (newX > maxRight) newX = maxRight;
					}
				}

				const shiftV = newX - pointX;

				this.draggingVLine.forEach(point => {
					point[0] += shiftV;
				});

				this.lastMouseX = newX;

				this.drawCanvas();
				return;
			}

			// cursor detection
			let line = this.findHLineAtPosition(mouseX, mouseY);

			if (line) {
				canvas.style.cursor = "ns-resize";
			} else {
				// cursor detection
				line = this.findVLineAtPosition(mouseX, mouseY);

				if (line) {
					canvas.style.cursor = "ew-resize";
				} else {
					canvas.style.cursor = "default";
				}
			}

			if (!this.checkIfMouseIsBetweenHorizontalLines(mouseX, mouseY))
				this.checkIfMouseIsBetweenVerticalLines(mouseX, mouseY);

			if (!this.checkIfMouseIsOnHorizontalLines(mouseX, mouseY))
				this.checkIfMouseIsOnVerticalLines(mouseX, mouseY);
		}, {passive: false});

		canvas.addEventListener("pointerup", () => {

			this.finishDragging();
			this.canvas.style.cursor = "grabbing";
		}, {passive: false});

		canvas.addEventListener("mouseleave", () => {

			this.finishDragging();
			canvas.style.cursor = "default";
		}, {passive: false});


	}

	addNewHorizontalLine() {

		let lineAbove = null;
		let lineBelow = null;

		if (this.horizontalLines.length === 0) {
			lineAbove = [[0, 0], [this.canvas.width, 0]];
			lineBelow = [[0, this.canvas.height], [this.canvas.width, this.canvas.height]];
		} else if (this.addLineCandidate.index === -1) {
			lineBelow = this.horizontalLines[this.addLineCandidate.index + 1];
			lineAbove = this.horizontalLines[this.addLineCandidate.index + 1].map(p => [...p]);
			lineAbove.forEach(p => p[1] = 0);
		} else if (this.addLineCandidate.index === this.horizontalLines.length - 1) {
			lineAbove = this.horizontalLines[this.addLineCandidate.index];
			lineBelow = this.horizontalLines[this.addLineCandidate.index].map(p => [...p]);
			lineBelow.forEach(p => p[1] = this.canvas.height);
		} else {
			lineAbove = this.horizontalLines[this.addLineCandidate.index];
			lineBelow = this.horizontalLines[this.addLineCandidate.index + 1];
		}


		const xs = [...new Set([
			...lineAbove.map(p => p[0]),
			...lineBelow.map(p => p[0])
		])].sort((a, b) => a - b);

		const newLine = [];
		xs.forEach(x => {

			const y1 = this.getYatX(lineAbove, x);
			const y2 = this.getYatX(lineBelow, x);

			newLine.push([x, (y1 + y2) / 2]);

		});

		if (this.addLineCandidate.index === this.horizontalLines.length - 1) {
			this.horizontalLines.push(newLine);
		} else {
			this.horizontalLines.splice(this.addLineCandidate.index + 1, 0, newLine);
		}

		this.addLineCandidate = null;

		this.saveHorizontalLines();
		this.drawCanvas();
	}

	addNewVerticalLine() {
		let lineLeft = null;
		let lineRight = null;

		if (this.addLineCandidate.index === -1) {
			lineRight = this.verticalLines[this.addLineCandidate.index + 1];
			lineLeft = this.verticalLines[this.addLineCandidate.index + 1].map(p => [...p]);
			lineLeft.forEach(p => p[0] = 0);
		} else if (this.addLineCandidate.index === this.verticalLines.length - 1) {
			lineLeft = this.verticalLines[this.addLineCandidate.index];
			lineRight = this.verticalLines[this.addLineCandidate.index].map(p => [...p]);
			lineRight.forEach(p => p[0] = this.canvas.width);
		} else {
			lineLeft = this.verticalLines[this.addLineCandidate.index];
			lineRight = this.verticalLines[this.addLineCandidate.index + 1];
		}


		const xs = [...new Set([
			...lineLeft.map(p => p[1]),
			...lineRight.map(p => p[1])
		])].sort((a, b) => a - b);

		const newLine = [];
		xs.forEach(y => {

			const x1 = this.getXatY(lineLeft, y);
			const x2 = this.getXatY(lineRight, y);

			newLine.push([(x1 + x2) / 2, y]);

		});

		if (this.addLineCandidate.index === this.verticalLines.length - 1) {
			this.verticalLines.push(newLine);
		} else {
			this.verticalLines.splice(this.addLineCandidate.index + 1, 0, newLine);
		}

		this.addLineCandidate = null;

		this.saveVerticalLines();
		this.drawCanvas();
	}

	getYatX(line, x) {

		for (let i = 0; i < line.length - 1; i++) {

			const [x1, y1] = line[i];
			const [x2, y2] = line[i + 1];

			if (x >= x1 && x <= x2) {

				const t = (x - x1) / (x2 - x1);
				return y1 + t * (y2 - y1);

			}
		}

		return line[line.length - 1][1];
	}

	getXatY(line, y) {

		for (let i = 0; i < line.length - 1; i++) {

			const [x1, y1] = line[i];
			const [x2, y2] = line[i + 1];

			if (y >= y1 && y <= y2) {

				const t = (y - y1) / (y2 - y1);
				return x1 + t * (x2 - x1);

			}
		}

		return line[line.length - 1][0];
	}

	checkIfMouseIsBetweenHorizontalLines(mouseX, mouseY) {

		if (mouseX < this.LEFT_ZONE) {

			for (let i = 0; i < this.horizontalLines.length - 1; i++) {

				let y1 = this.horizontalLines[i][0][1];
				let y2 = this.horizontalLines[i + 1][0][1];

				if (i === 0) {
					if (mouseY < y1 - this.BETWEEN_TOLERANCE) {

						this.addLineCandidate = {
							type: this.HORIZONTAL,
							index: -1,
							y: y1 / 2
						};

						if (mouseX < 30)
							canvas.style.cursor = "pointer";

						this.drawCanvas();
						this.drawAddIcon(20, y1 / 2);

						return true;
					}
				}


				if (mouseY > y1 + this.BETWEEN_TOLERANCE && mouseY < y2 - this.BETWEEN_TOLERANCE) {

					this.addLineCandidate = {
						type: this.HORIZONTAL,
						index: i,
						y: mouseY
					};

					if (mouseX < 30)
						canvas.style.cursor = "pointer";

					this.drawCanvas();
					this.drawAddIcon(20, y1 + (y2 - y1) / 2);

					return true;
				}

			}

			let i = this.horizontalLines.length - 1;

			let y1 = 0;
			if (this.horizontalLines.length > 0)
				y1 = this.horizontalLines[i][0][1];

			let y2 = this.canvas.height;

			if (mouseY > y1 - this.BETWEEN_TOLERANCE) {

				this.addLineCandidate = {
					type: this.HORIZONTAL,
					index: i,
					y: y1 + (y2 - y1) / 2
				};

				if (mouseX < 30)
					canvas.style.cursor = "pointer";

				this.drawCanvas();
				this.drawAddIcon(20, y1 + (y2 - y1) / 2);

				return true;
			}

		}

		if (this.addLineCandidate) {
			this.drawCanvas();
			this.addLineCandidate = null;
		}
		return false;
	}

	checkIfMouseIsBetweenVerticalLines(mouseX, mouseY) {

		if (mouseY < this.TOP_ZONE) {

			for (let i = 0; i < this.verticalLines.length - 1; i++) {

				let x1 = this.verticalLines[i][0][0];
				let x2 = this.verticalLines[i + 1][0][0];

				if (i === 0) {
					if (mouseX < x1 - this.BETWEEN_TOLERANCE) {

						this.addLineCandidate = {
							type: this.VERTICAL,
							index: -1,
							x: x1 / 2
						};

						if (mouseY < 30)
							canvas.style.cursor = "pointer";

						this.drawCanvas();
						this.drawAddIcon(x1 / 2, 20);

						return true;
					}
				}


				if (mouseX > x1 + this.BETWEEN_TOLERANCE && mouseX < x2 - this.BETWEEN_TOLERANCE) {

					this.addLineCandidate = {
						type: this.VERTICAL,
						index: i,
						x: mouseX
					};

					if (mouseY < 30)
						canvas.style.cursor = "pointer";

					this.drawCanvas();
					this.drawAddIcon(x1 + (x2 - x1) / 2, 20);

					return true;
				}

			}

			let i = this.verticalLines.length - 1;
			let x1 = this.verticalLines[i][0][0];
			let x2 = this.canvas.width;

			if (mouseX > x1 - this.BETWEEN_TOLERANCE) {

				this.addLineCandidate = {
					type: this.VERTICAL,
					index: i,
					x: x1 + (x2 - x1) / 2
				};

				if (mouseY < 30)
					canvas.style.cursor = "pointer";

				this.drawCanvas();
				this.drawAddIcon(x1 + (x2 - x1) / 2, 20);

				return true;
			}

		}

		if (this.addLineCandidate) {
			this.drawCanvas();
			this.addLineCandidate = null;
		}
		return false;
	}

	checkIfMouseIsOnHorizontalLines(mouseX, mouseY) {

		if (mouseX < this.LEFT_ZONE) {

			const line = this.findHLineAtPosition(mouseX, mouseY);

			if (line) {

				this.deleteLineCandidate = {
					type: this.HORIZONTAL,
					line: line[1],
					index: line[0],
					y: line[1][0][1]
				};

				if (mouseX < 30)
					this.canvas.style.cursor = "pointer";

				this.drawCanvas();
				this.drawDeleteIcon(20, this.deleteLineCandidate.y);
				return true;
			} else {
				this.deleteLineCandidate = null;
			}
		}

		if (this.deleteLineCandidate) {
			this.drawCanvas();
			this.deleteLineCandidate = null;
		}
		return false;
	}

	checkIfMouseIsOnVerticalLines(mouseX, mouseY) {

		if (mouseY < this.TOP_ZONE) {

			const line = this.findVLineAtPosition(mouseX, mouseY);

			if (line) {

				this.deleteLineCandidate = {
					type: this.VERTICAL,
					line: line[1],
					index: line[0],
					x: line[1][0][0]
				};

				if (mouseY < 30)
					this.canvas.style.cursor = "pointer";

				this.drawCanvas();
				this.drawDeleteIcon(this.deleteLineCandidate.x, 20);
				return true;
			} else {
				this.deleteLineCandidate = null;
			}
		}

		if (this.deleteLineCandidate) {
			this.drawCanvas();
			this.deleteLineCandidate = null;
		}
		return false;
	}

	drawAddIcon(x, y) {

		this.ctx.save();

		this.ctx.globalCompositeOperation = "source-over";

		this.ctx.setLineDash([]);
		this.ctx.fillStyle = "white";
		this.ctx.strokeStyle = "black";
		this.ctx.lineWidth = 2;

		this.ctx.beginPath();
		this.ctx.arc(x, y, 8, 0, Math.PI * 2);
		this.ctx.fill();
		this.ctx.stroke();

		this.ctx.beginPath();
		this.ctx.moveTo(x - 4, y);
		this.ctx.lineTo(x + 4, y);
		this.ctx.moveTo(x, y - 4);
		this.ctx.lineTo(x, y + 4);
		this.ctx.stroke();

		this.ctx.restore();

	}

	drawDeleteIcon(x, y) {

		this.ctx.save();

		this.ctx.globalCompositeOperation = "source-over";

		this.ctx.fillStyle = "#ff4d4d";
		this.ctx.strokeStyle = "white";
		this.ctx.lineWidth = 2;

		this.ctx.beginPath();
		this.ctx.arc(x, y, 8, 0, Math.PI * 2);
		this.ctx.fill();

		this.ctx.beginPath();
		this.ctx.moveTo(x - 4, y);
		this.ctx.lineTo(x + 4, y);
		this.ctx.stroke();

		this.ctx.restore();
	}

	finishDragging() {
		if (this.draggingHLineIndex !== -1)
			this.saveHorizontalLines();
		else if (this.draggingVLineIndex !== -1)
			this.saveVerticalLines();

		this.draggingHLine = null;
		this.draggingHLineIndex = -1;
		this.offsetY = 0;
		this.lastMouseY = 0;

		this.draggingVLine = null;
		this.draggingVLineIndex = -1;
		this.offsetX = 0;
		this.lastMouseX = 0;
	}

	saveHorizontalLines() {
		let obj = document.getElementById(this.horizontalEditBox);
		obj.value = JSON.stringify(this.horizontalLines);
	}

	saveVerticalLines() {
		let obj = document.getElementById(this.verticalEditBox);
		obj.value = JSON.stringify(this.verticalLines);
	}
}
