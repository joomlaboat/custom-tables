let AllTables = [];
let BlocksPositions = [];
let TableCategoryID = 0;

Raphael.fn.connection = function (obj1, obj2, line, bg) {
    if (obj1.line && obj1.from && obj1.to) {
        line = obj1;
        obj1 = line.from;
        obj2 = line.to;
    }
    let bb1 = obj1.getBBox(),
        bb2 = obj2.getBBox(),
        p = [{x: bb1.x + bb1.width / 2, y: bb1.y - 1},
            {x: bb1.x + bb1.width / 2, y: bb1.y + bb1.height + 1},
            {x: bb1.x - 1, y: bb1.y + bb1.height / 2},
            {x: bb1.x + bb1.width + 1, y: bb1.y + bb1.height / 2},
            {x: bb2.x + bb2.width / 2, y: bb2.y - 1},
            {x: bb2.x + bb2.width / 2, y: bb2.y + bb2.height + 1},
            {x: bb2.x - 1, y: bb2.y + bb2.height / 2},
            {x: bb2.x + bb2.width + 1, y: bb2.y + bb2.height / 2}],
        d = {}, dis = [];
    for (let i = 0; i < 4; i++) {
        for (let j = 4; j < 8; j++) {
            let dx = Math.abs(p[i].x - p[j].x),
                dy = Math.abs(p[i].y - p[j].y);
            if ((i == j - 4) || (((i != 3 && j != 6) || p[i].x < p[j].x) && ((i != 2 && j != 7) || p[i].x > p[j].x) && ((i != 0 && j != 5) || p[i].y > p[j].y) && ((i != 1 && j != 4) || p[i].y < p[j].y))) {
                dis.push(dx + dy);
                d[dis[dis.length - 1]] = [i, j];
            }
        }
    }
    if (dis.length == 0) {
        let res = [0, 4];
    } else {
        res = d[Math.min.apply(Math, dis)];
    }
    let x1 = p[res[0]].x,
        y1 = p[res[0]].y,
        x4 = p[res[1]].x,
        y4 = p[res[1]].y;
    dx = Math.max(Math.abs(x1 - x4) / 2, 10);
    dy = Math.max(Math.abs(y1 - y4) / 2, 10);
    let x2 = [x1, x1, x1 - dx, x1 + dx][res[0]].toFixed(3),
        y2 = [y1 - dy, y1 + dy, y1, y1][res[0]].toFixed(3),
        x3 = [0, 0, 0, 0, x4, x4, x4 - dx, x4 + dx][res[1]].toFixed(3),
        y3 = [0, 0, 0, 0, y1 + dy, y1 - dy, y4, y4][res[1]].toFixed(3);
    let path = ["M", x1.toFixed(3), y1.toFixed(3), "C", x2, y2, x3, y3, x4.toFixed(3), y4.toFixed(3)].join(",");
    if (line && line.line) {
        line.bg && line.bg.attr({path: path});
        line.line.attr({path: path});
    } else {
        let color = typeof line == "string" ? line : "#000";
        return {
            //bg: bg && bg.split && this.path(path).attr({stroke: bg.split("|")[0], fill: "none", "stroke-width": bg.split("|")[1] || 3}),
            bg: bg && bg.split && this.path(path).attr({stroke: bg.split("|")[0], fill: "none", "stroke-width": '10'}),
            line: this.path(path).attr({stroke: color, fill: "none", "stroke-width": '2'}),
            from: obj1,
            to: obj2
        };
    }
};

let TableListPresenter = function (box_width, box_height, tables) {

    let dragger = function () {
            this.set.oBB = this.set.getBBox();
            //this.animate({"fill-opacity": .2}, 500);
        },

        move = function (dx, dy) {
            let bb = this.set.getBBox();

            this.set.translate(this.set.oBB.x - bb.x + dx, this.set.oBB.y - bb.y + dy);

            BlocksPositions[this.index].x = bb.x;
            BlocksPositions[this.index].y = bb.y;


            for (let i = connections.length; i--;) {
                paper.connection(connections[i]);
            }
            //paper.safari();
        },
        up = function () {
            document.cookie = "jCustomTablesSchema" + (TableCategoryID > 0 ? TableCategoryID : '') + "=" + JSON.stringify(BlocksPositions) + ';max-age=31536000;SameSite=Strict';
        },

        connections = [];

    connection_fields = [];
    connection_tables = [];

    let paper = null;
    let sets = [];

    let calculatedColumnWidth = 120;
    let calculatedLeftOffset = 25;

    if (tables.length < 10)
        calculatedLeftOffset = 100;

    let settings = {
        paper: {
            width: box_height, // mixed up
            height: box_width, // mixed up
            table: {
                gridColumns: (box_width) / (calculatedColumnWidth + calculatedLeftOffset),
                topOffset: calculatedLeftOffset,
                leftOffset: calculatedLeftOffset
            }
        },
        column: {
            rect: {
                width: calculatedColumnWidth,
                height: 20
            },
            text: {
                topOffset: 10,
                leftOffset: 5
            }
        }
    };

    let tableGrid = [];
    for (let i = settings.paper.table.gridColumns - 1; i >= 0; i--) {
        tableGrid.push(0);
    }

    let currentGridColumn = 0;

    this.draw = function (element) {
        paper = new Raphael(
            element,
            settings.paper.height,
            settings.paper.width
        );

        let c = getCookie('jCustomTablesSchema' + (TableCategoryID > 0 ? TableCategoryID : ''));
        if (c && c != "")
            BlocksPositions = JSON.parse(c);

        for (let tableIndex = 0; tableIndex < tables.length; tableIndex++) {
            drawTable(tables[tableIndex]);
        }

        addConnections();

        for (let i = 0, ii = sets.length; i < ii; i++) {
            //let color = Raphael.getColor();
            sets[i].attr({cursor: "move"});
            sets[i].drag(move, dragger, up);
        }

    }

    function addConnections() {
        for (let i = 0; i < connection_fields.length; i++) {
            let f = connection_fields[i];

            for (let t = 0; t < connection_tables.length; t++) {
                let tbl = connection_tables[t];
                if (tbl.name == f.join_table) {
                    connections.push(paper.connection(sets[tbl.table_object_index][0], sets[f.table_object_index][f.field_object_index], f.joincolor));
                    break;
                }
            }
        }
    }

    function printRow(set, rectLeft, rectTop, text, bg_color, color, align, index) {
        let header_rect = paper.rect(
            rectLeft,
            rectTop,
            settings.column.rect.width,
            settings.column.rect.height
        ).attr({'fill': bg_color});

        let tablename_text = paper
            .text(
                rectLeft + settings.column.rect.width / 2,
                rectTop + settings.column.text.topOffset,
                text
            ).attr({'text-anchor': align, 'fill': color});

        set.push(header_rect);
        set.push(tablename_text);

        header_rect.set = set;
        header_rect.index = index;
        tablename_text.set = set;
        tablename_text.index = index;

        rectTop += settings.column.rect.height;
        return rectTop;
    }

    function drawTable(table) {

        let index = sets.length;

        let set = paper.set();
        let rectLeft = 0;
        let rectTop = 0;

        if (BlocksPositions.length > index) {
            rectLeft = BlocksPositions[index].x;
            rectTop = BlocksPositions[index].y;
        } else {
            rectLeft = settings.paper.table.leftOffset + (currentGridColumn) * (settings.column.rect.width + settings.paper.table.leftOffset);
            rectTop = tableGrid[currentGridColumn] || settings.paper.table.topOffset;
            BlocksPositions.push({'x': rectLeft, 'y': rectTop});
        }

        rectTop = printRow(set, rectLeft, rectTop, table.name, table.color, table.text_color, 'center', sets.length);

        for (let i = 0; i < table.columns.length; i++) {
            let column = table.columns[i].name;

            let color = "#000000";
            if (table.columns[i].type == 'sqljoin' || table.columns[i].type == 'records') {
                if (table.columns[i].joincolor != '')
                    color = table.columns[i].joincolor;
            }

            let rect = paper.rect(
                rectLeft,
                rectTop,
                settings.column.rect.width,
                settings.column.rect.height
            ).attr({'fill': "#f8f8f8"});

            let text = paper
                .text(
                    rectLeft + settings.column.text.leftOffset,
                    rectTop + settings.column.text.topOffset,
                    column
                ).attr({'text-anchor': 'start', 'fill': color});

            rectTop += settings.column.rect.height;


            if (table.columns[i].type == 'sqljoin' || table.columns[i].type == 'records') {
                connection_fields.push({
                    'join_table': table.columns[i].join,
                    'table_object_index': sets.length,
                    'field_object_index': set.length,
                    'joincolor': table.columns[i].joincolor
                });
            }
            set.push(rect);
            set.push(text);


            rect.set = set;
            rect.index = index;
            text.set = set;
            text.index = index;
        }

        connection_tables.push({
            'name': table.name,
            'table_object_index': sets.length,
        });

        sets.push(set);
        updateTableGrid(rectTop);
    }

    /**
     * Update the current column height in grid
     * and choose the shortest one for the next table
     */
    function updateTableGrid(rectTop) {
        tableGrid[currentGridColumn] = rectTop + settings.paper.table.topOffset;
        let minColumnHeight = tableGrid[0];
        let shortestColumn = 0;
        for (let i = 1; i < tableGrid.length; i++) {
            if (tableGrid[i] < minColumnHeight) {
                shortestColumn = i;
                minColumnHeight = tableGrid[i];
            }
        }
        ;
        currentGridColumn = shortestColumn;
    }

    function getCookie(cname) {
        let name = cname + "=";
        let decodedCookie = decodeURIComponent(document.cookie);
        let ca = decodedCookie.split(';');

        for (let i = 0; i < ca.length; i++) {
            let c = ca[i];
            while (c.charAt(0) == ' ')
                c = c.substring(1);

            if (c.indexOf(name) == 0)
                return c.substring(name.length, c.length);
        }
        return "";
    }
};

window.onload = function () {

    let box = document.getElementById('canvas_container');
    let width = box.clientWidth;
    let height = box.clientHeight;

    let tableListPresenter = new TableListPresenter(width, height, AllTables);

    tableListPresenter.draw(document.getElementById('canvas_container'));
}  
