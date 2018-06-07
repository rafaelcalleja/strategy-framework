/****************************************************************************************************
 *
 * jsqrcode lib
 *
 * https://github.com/LazarSoft/jsqrcode
 *
 * Changed:
 * - Code wrapped in a function scope
 * - Global variables removed.
 *
 ****************************************************************************************************/
(function (root) {
    'use strict';

    var GridSampler = {};
    GridSampler.checkAndNudgePoints = function (f, e) {
        var d = qrcode.width;
        var b = qrcode.height;
        var c = true;
        for (var g = 0; g < e.Length && c; g += 2) {
            var a = Math.floor(e[g]);
            var h = Math.floor(e[g + 1]);
            if (a < -1 || a > d || h < -1 || h > b) {
                throw"Error.checkAndNudgePoints "
            }
            c = false;
            if (a == -1) {
                e[g] = 0;
                c = true
            } else {
                if (a == d) {
                    e[g] = d - 1;
                    c = true
                }
            }
            if (h == -1) {
                e[g + 1] = 0;
                c = true
            } else {
                if (h == b) {
                    e[g + 1] = b - 1;
                    c = true
                }
            }
        }
        c = true;
        for (var g = e.Length - 2; g >= 0 && c; g -= 2) {
            var a = Math.floor(e[g]);
            var h = Math.floor(e[g + 1]);
            if (a < -1 || a > d || h < -1 || h > b) {
                throw"Error.checkAndNudgePoints "
            }
            c = false;
            if (a == -1) {
                e[g] = 0;
                c = true
            } else {
                if (a == d) {
                    e[g] = d - 1;
                    c = true
                }
            }
            if (h == -1) {
                e[g + 1] = 0;
                c = true
            } else {
                if (h == b) {
                    e[g + 1] = b - 1;
                    c = true
                }
            }
        }
    };
    GridSampler.sampleGrid3 = function (b, d, a) {
        var l = new BitMatrix(d);
        var k = new Array(d << 1);
        for (var g = 0; g < d; g++) {
            var h = k.length;
            var j = g + 0.5;
            for (var i = 0; i < h; i += 2) {
                k[i] = (i >> 1) + 0.5;
                k[i + 1] = j
            }
            a.transformPoints1(k);
            GridSampler.checkAndNudgePoints(b, k);
            try {
                for (var i = 0; i < h; i += 2) {
                    var e = (Math.floor(k[i]) * 4) + (Math.floor(k[i + 1]) * qrcode.width * 4);
                    var f = b[Math.floor(k[i]) + qrcode.width * Math.floor(k[i + 1])];
                    qrcode.imagedata.data[e] = f ? 255 : 0;
                    qrcode.imagedata.data[e + 1] = f ? 255 : 0;
                    qrcode.imagedata.data[e + 2] = 0;
                    qrcode.imagedata.data[e + 3] = 255;
                    if (f) {
                        l.set_Renamed(i >> 1, g)
                    }
                }
            } catch (c) {
                throw"Error.checkAndNudgePoints"
            }
        }
        return l
    };
    GridSampler.sampleGridx = function (h, o, l, k, r, q, b, a, f, e, n, m, t, s, d, c, j, i) {
        var g = PerspectiveTransform.quadrilateralToQuadrilateral(l, k, r, q, b, a, f, e, n, m, t, s, d, c, j, i);
        return GridSampler.sampleGrid3(h, o, g)
    };
    function ECB(b, a) {
        this.count = b;
        this.dataCodewords = a;
        this.__defineGetter__("Count", function () {
            return this.count
        });
        this.__defineGetter__("DataCodewords", function () {
            return this.dataCodewords
        })
    }

    function ECBlocks(a, c, b) {
        this.ecCodewordsPerBlock = a;
        if (b) {
            this.ecBlocks = new Array(c, b)
        } else {
            this.ecBlocks = new Array(c)
        }
        this.__defineGetter__("ECCodewordsPerBlock", function () {
            return this.ecCodewordsPerBlock
        });
        this.__defineGetter__("TotalECCodewords", function () {
            return this.ecCodewordsPerBlock * this.NumBlocks
        });
        this.__defineGetter__("NumBlocks", function () {
            var e = 0;
            for (var d = 0; d < this.ecBlocks.length; d++) {
                e += this.ecBlocks[d].length
            }
            return e
        });
        this.getECBlocks = function () {
            return this.ecBlocks
        }
    }

    function Version(k, l, h, g, f, e) {
        this.versionNumber = k;
        this.alignmentPatternCenters = l;
        this.ecBlocks = new Array(h, g, f, e);
        var j = 0;
        var b = h.ECCodewordsPerBlock;
        var a = h.getECBlocks();
        for (var d = 0; d < a.length; d++) {
            var c = a[d];
            j += c.Count * (c.DataCodewords + b)
        }
        this.totalCodewords = j;
        this.__defineGetter__("VersionNumber", function () {
            return this.versionNumber
        });
        this.__defineGetter__("AlignmentPatternCenters", function () {
            return this.alignmentPatternCenters
        });
        this.__defineGetter__("TotalCodewords", function () {
            return this.totalCodewords
        });
        this.__defineGetter__("DimensionForVersion", function () {
            return 17 + 4 * this.versionNumber
        });
        this.buildFunctionPattern = function () {
            var r = this.DimensionForVersion;
            var o = new BitMatrix(r);
            o.setRegion(0, 0, 9, 9);
            o.setRegion(r - 8, 0, 8, 9);
            o.setRegion(0, r - 8, 9, 8);
            var n = this.alignmentPatternCenters.length;
            for (var m = 0; m < n; m++) {
                var q = this.alignmentPatternCenters[m] - 2;
                for (var s = 0; s < n; s++) {
                    if ((m == 0 && (s == 0 || s == n - 1)) || (m == n - 1 && s == 0)) {
                        continue
                    }
                    o.setRegion(this.alignmentPatternCenters[s] - 2, q, 5, 5)
                }
            }
            o.setRegion(6, 9, 1, r - 17);
            o.setRegion(9, 6, r - 17, 1);
            if (this.versionNumber > 6) {
                o.setRegion(r - 11, 0, 3, 6);
                o.setRegion(0, r - 11, 6, 3)
            }
            return o
        };
        this.getECBlocksForLevel = function (i) {
            return this.ecBlocks[i.ordinal()]
        }
    }

    Version.VERSION_DECODE_INFO = new Array(31892, 34236, 39577, 42195, 48118, 51042, 55367, 58893, 63784, 68472, 70749, 76311, 79154, 84390, 87683, 92361, 96236, 102084, 102881, 110507, 110734, 117786, 119615, 126325, 127568, 133589, 136944, 141498, 145311, 150283, 152622, 158308, 161089, 167017);
    Version.VERSIONS = buildVersions();
    Version.getVersionForNumber = function (a) {
        if (a < 1 || a > 40) {
            throw"ArgumentException"
        }
        return Version.VERSIONS[a - 1]
    };
    Version.getProvisionalVersionForDimension = function (b) {
        if (b % 4 != 1) {
            throw"Error getProvisionalVersionForDimension"
        }
        try {
            return Version.getVersionForNumber((b - 17) >> 2)
        } catch (a) {
            throw"Error getVersionForNumber"
        }
    };
    Version.decodeVersionInformation = function (d) {
        var b = 4294967295;
        var f = 0;
        for (var c = 0; c < Version.VERSION_DECODE_INFO.length; c++) {
            var a = Version.VERSION_DECODE_INFO[c];
            if (a == d) {
                return this.getVersionForNumber(c + 7)
            }
            var e = FormatInformation.numBitsDiffering(d, a);
            if (e < b) {
                f = c + 7;
                b = e
            }
        }
        if (b <= 3) {
            return this.getVersionForNumber(f)
        }
        return null
    };
    function buildVersions() {
        return new Array(new Version(1, new Array(), new ECBlocks(7, new ECB(1, 19)), new ECBlocks(10, new ECB(1, 16)), new ECBlocks(13, new ECB(1, 13)), new ECBlocks(17, new ECB(1, 9))), new Version(2, new Array(6, 18), new ECBlocks(10, new ECB(1, 34)), new ECBlocks(16, new ECB(1, 28)), new ECBlocks(22, new ECB(1, 22)), new ECBlocks(28, new ECB(1, 16))), new Version(3, new Array(6, 22), new ECBlocks(15, new ECB(1, 55)), new ECBlocks(26, new ECB(1, 44)), new ECBlocks(18, new ECB(2, 17)), new ECBlocks(22, new ECB(2, 13))), new Version(4, new Array(6, 26), new ECBlocks(20, new ECB(1, 80)), new ECBlocks(18, new ECB(2, 32)), new ECBlocks(26, new ECB(2, 24)), new ECBlocks(16, new ECB(4, 9))), new Version(5, new Array(6, 30), new ECBlocks(26, new ECB(1, 108)), new ECBlocks(24, new ECB(2, 43)), new ECBlocks(18, new ECB(2, 15), new ECB(2, 16)), new ECBlocks(22, new ECB(2, 11), new ECB(2, 12))), new Version(6, new Array(6, 34), new ECBlocks(18, new ECB(2, 68)), new ECBlocks(16, new ECB(4, 27)), new ECBlocks(24, new ECB(4, 19)), new ECBlocks(28, new ECB(4, 15))), new Version(7, new Array(6, 22, 38), new ECBlocks(20, new ECB(2, 78)), new ECBlocks(18, new ECB(4, 31)), new ECBlocks(18, new ECB(2, 14), new ECB(4, 15)), new ECBlocks(26, new ECB(4, 13), new ECB(1, 14))), new Version(8, new Array(6, 24, 42), new ECBlocks(24, new ECB(2, 97)), new ECBlocks(22, new ECB(2, 38), new ECB(2, 39)), new ECBlocks(22, new ECB(4, 18), new ECB(2, 19)), new ECBlocks(26, new ECB(4, 14), new ECB(2, 15))), new Version(9, new Array(6, 26, 46), new ECBlocks(30, new ECB(2, 116)), new ECBlocks(22, new ECB(3, 36), new ECB(2, 37)), new ECBlocks(20, new ECB(4, 16), new ECB(4, 17)), new ECBlocks(24, new ECB(4, 12), new ECB(4, 13))), new Version(10, new Array(6, 28, 50), new ECBlocks(18, new ECB(2, 68), new ECB(2, 69)), new ECBlocks(26, new ECB(4, 43), new ECB(1, 44)), new ECBlocks(24, new ECB(6, 19), new ECB(2, 20)), new ECBlocks(28, new ECB(6, 15), new ECB(2, 16))), new Version(11, new Array(6, 30, 54), new ECBlocks(20, new ECB(4, 81)), new ECBlocks(30, new ECB(1, 50), new ECB(4, 51)), new ECBlocks(28, new ECB(4, 22), new ECB(4, 23)), new ECBlocks(24, new ECB(3, 12), new ECB(8, 13))), new Version(12, new Array(6, 32, 58), new ECBlocks(24, new ECB(2, 92), new ECB(2, 93)), new ECBlocks(22, new ECB(6, 36), new ECB(2, 37)), new ECBlocks(26, new ECB(4, 20), new ECB(6, 21)), new ECBlocks(28, new ECB(7, 14), new ECB(4, 15))), new Version(13, new Array(6, 34, 62), new ECBlocks(26, new ECB(4, 107)), new ECBlocks(22, new ECB(8, 37), new ECB(1, 38)), new ECBlocks(24, new ECB(8, 20), new ECB(4, 21)), new ECBlocks(22, new ECB(12, 11), new ECB(4, 12))), new Version(14, new Array(6, 26, 46, 66), new ECBlocks(30, new ECB(3, 115), new ECB(1, 116)), new ECBlocks(24, new ECB(4, 40), new ECB(5, 41)), new ECBlocks(20, new ECB(11, 16), new ECB(5, 17)), new ECBlocks(24, new ECB(11, 12), new ECB(5, 13))), new Version(15, new Array(6, 26, 48, 70), new ECBlocks(22, new ECB(5, 87), new ECB(1, 88)), new ECBlocks(24, new ECB(5, 41), new ECB(5, 42)), new ECBlocks(30, new ECB(5, 24), new ECB(7, 25)), new ECBlocks(24, new ECB(11, 12), new ECB(7, 13))), new Version(16, new Array(6, 26, 50, 74), new ECBlocks(24, new ECB(5, 98), new ECB(1, 99)), new ECBlocks(28, new ECB(7, 45), new ECB(3, 46)), new ECBlocks(24, new ECB(15, 19), new ECB(2, 20)), new ECBlocks(30, new ECB(3, 15), new ECB(13, 16))), new Version(17, new Array(6, 30, 54, 78), new ECBlocks(28, new ECB(1, 107), new ECB(5, 108)), new ECBlocks(28, new ECB(10, 46), new ECB(1, 47)), new ECBlocks(28, new ECB(1, 22), new ECB(15, 23)), new ECBlocks(28, new ECB(2, 14), new ECB(17, 15))), new Version(18, new Array(6, 30, 56, 82), new ECBlocks(30, new ECB(5, 120), new ECB(1, 121)), new ECBlocks(26, new ECB(9, 43), new ECB(4, 44)), new ECBlocks(28, new ECB(17, 22), new ECB(1, 23)), new ECBlocks(28, new ECB(2, 14), new ECB(19, 15))), new Version(19, new Array(6, 30, 58, 86), new ECBlocks(28, new ECB(3, 113), new ECB(4, 114)), new ECBlocks(26, new ECB(3, 44), new ECB(11, 45)), new ECBlocks(26, new ECB(17, 21), new ECB(4, 22)), new ECBlocks(26, new ECB(9, 13), new ECB(16, 14))), new Version(20, new Array(6, 34, 62, 90), new ECBlocks(28, new ECB(3, 107), new ECB(5, 108)), new ECBlocks(26, new ECB(3, 41), new ECB(13, 42)), new ECBlocks(30, new ECB(15, 24), new ECB(5, 25)), new ECBlocks(28, new ECB(15, 15), new ECB(10, 16))), new Version(21, new Array(6, 28, 50, 72, 94), new ECBlocks(28, new ECB(4, 116), new ECB(4, 117)), new ECBlocks(26, new ECB(17, 42)), new ECBlocks(28, new ECB(17, 22), new ECB(6, 23)), new ECBlocks(30, new ECB(19, 16), new ECB(6, 17))), new Version(22, new Array(6, 26, 50, 74, 98), new ECBlocks(28, new ECB(2, 111), new ECB(7, 112)), new ECBlocks(28, new ECB(17, 46)), new ECBlocks(30, new ECB(7, 24), new ECB(16, 25)), new ECBlocks(24, new ECB(34, 13))), new Version(23, new Array(6, 30, 54, 74, 102), new ECBlocks(30, new ECB(4, 121), new ECB(5, 122)), new ECBlocks(28, new ECB(4, 47), new ECB(14, 48)), new ECBlocks(30, new ECB(11, 24), new ECB(14, 25)), new ECBlocks(30, new ECB(16, 15), new ECB(14, 16))), new Version(24, new Array(6, 28, 54, 80, 106), new ECBlocks(30, new ECB(6, 117), new ECB(4, 118)), new ECBlocks(28, new ECB(6, 45), new ECB(14, 46)), new ECBlocks(30, new ECB(11, 24), new ECB(16, 25)), new ECBlocks(30, new ECB(30, 16), new ECB(2, 17))), new Version(25, new Array(6, 32, 58, 84, 110), new ECBlocks(26, new ECB(8, 106), new ECB(4, 107)), new ECBlocks(28, new ECB(8, 47), new ECB(13, 48)), new ECBlocks(30, new ECB(7, 24), new ECB(22, 25)), new ECBlocks(30, new ECB(22, 15), new ECB(13, 16))), new Version(26, new Array(6, 30, 58, 86, 114), new ECBlocks(28, new ECB(10, 114), new ECB(2, 115)), new ECBlocks(28, new ECB(19, 46), new ECB(4, 47)), new ECBlocks(28, new ECB(28, 22), new ECB(6, 23)), new ECBlocks(30, new ECB(33, 16), new ECB(4, 17))), new Version(27, new Array(6, 34, 62, 90, 118), new ECBlocks(30, new ECB(8, 122), new ECB(4, 123)), new ECBlocks(28, new ECB(22, 45), new ECB(3, 46)), new ECBlocks(30, new ECB(8, 23), new ECB(26, 24)), new ECBlocks(30, new ECB(12, 15), new ECB(28, 16))), new Version(28, new Array(6, 26, 50, 74, 98, 122), new ECBlocks(30, new ECB(3, 117), new ECB(10, 118)), new ECBlocks(28, new ECB(3, 45), new ECB(23, 46)), new ECBlocks(30, new ECB(4, 24), new ECB(31, 25)), new ECBlocks(30, new ECB(11, 15), new ECB(31, 16))), new Version(29, new Array(6, 30, 54, 78, 102, 126), new ECBlocks(30, new ECB(7, 116), new ECB(7, 117)), new ECBlocks(28, new ECB(21, 45), new ECB(7, 46)), new ECBlocks(30, new ECB(1, 23), new ECB(37, 24)), new ECBlocks(30, new ECB(19, 15), new ECB(26, 16))), new Version(30, new Array(6, 26, 52, 78, 104, 130), new ECBlocks(30, new ECB(5, 115), new ECB(10, 116)), new ECBlocks(28, new ECB(19, 47), new ECB(10, 48)), new ECBlocks(30, new ECB(15, 24), new ECB(25, 25)), new ECBlocks(30, new ECB(23, 15), new ECB(25, 16))), new Version(31, new Array(6, 30, 56, 82, 108, 134), new ECBlocks(30, new ECB(13, 115), new ECB(3, 116)), new ECBlocks(28, new ECB(2, 46), new ECB(29, 47)), new ECBlocks(30, new ECB(42, 24), new ECB(1, 25)), new ECBlocks(30, new ECB(23, 15), new ECB(28, 16))), new Version(32, new Array(6, 34, 60, 86, 112, 138), new ECBlocks(30, new ECB(17, 115)), new ECBlocks(28, new ECB(10, 46), new ECB(23, 47)), new ECBlocks(30, new ECB(10, 24), new ECB(35, 25)), new ECBlocks(30, new ECB(19, 15), new ECB(35, 16))), new Version(33, new Array(6, 30, 58, 86, 114, 142), new ECBlocks(30, new ECB(17, 115), new ECB(1, 116)), new ECBlocks(28, new ECB(14, 46), new ECB(21, 47)), new ECBlocks(30, new ECB(29, 24), new ECB(19, 25)), new ECBlocks(30, new ECB(11, 15), new ECB(46, 16))), new Version(34, new Array(6, 34, 62, 90, 118, 146), new ECBlocks(30, new ECB(13, 115), new ECB(6, 116)), new ECBlocks(28, new ECB(14, 46), new ECB(23, 47)), new ECBlocks(30, new ECB(44, 24), new ECB(7, 25)), new ECBlocks(30, new ECB(59, 16), new ECB(1, 17))), new Version(35, new Array(6, 30, 54, 78, 102, 126, 150), new ECBlocks(30, new ECB(12, 121), new ECB(7, 122)), new ECBlocks(28, new ECB(12, 47), new ECB(26, 48)), new ECBlocks(30, new ECB(39, 24), new ECB(14, 25)), new ECBlocks(30, new ECB(22, 15), new ECB(41, 16))), new Version(36, new Array(6, 24, 50, 76, 102, 128, 154), new ECBlocks(30, new ECB(6, 121), new ECB(14, 122)), new ECBlocks(28, new ECB(6, 47), new ECB(34, 48)), new ECBlocks(30, new ECB(46, 24), new ECB(10, 25)), new ECBlocks(30, new ECB(2, 15), new ECB(64, 16))), new Version(37, new Array(6, 28, 54, 80, 106, 132, 158), new ECBlocks(30, new ECB(17, 122), new ECB(4, 123)), new ECBlocks(28, new ECB(29, 46), new ECB(14, 47)), new ECBlocks(30, new ECB(49, 24), new ECB(10, 25)), new ECBlocks(30, new ECB(24, 15), new ECB(46, 16))), new Version(38, new Array(6, 32, 58, 84, 110, 136, 162), new ECBlocks(30, new ECB(4, 122), new ECB(18, 123)), new ECBlocks(28, new ECB(13, 46), new ECB(32, 47)), new ECBlocks(30, new ECB(48, 24), new ECB(14, 25)), new ECBlocks(30, new ECB(42, 15), new ECB(32, 16))), new Version(39, new Array(6, 26, 54, 82, 110, 138, 166), new ECBlocks(30, new ECB(20, 117), new ECB(4, 118)), new ECBlocks(28, new ECB(40, 47), new ECB(7, 48)), new ECBlocks(30, new ECB(43, 24), new ECB(22, 25)), new ECBlocks(30, new ECB(10, 15), new ECB(67, 16))), new Version(40, new Array(6, 30, 58, 86, 114, 142, 170), new ECBlocks(30, new ECB(19, 118), new ECB(6, 119)), new ECBlocks(28, new ECB(18, 47), new ECB(31, 48)), new ECBlocks(30, new ECB(34, 24), new ECB(34, 25)), new ECBlocks(30, new ECB(20, 15), new ECB(61, 16))))
    }

    function PerspectiveTransform(i, f, c, h, e, b, g, d, a) {
        this.a11 = i;
        this.a12 = h;
        this.a13 = g;
        this.a21 = f;
        this.a22 = e;
        this.a23 = d;
        this.a31 = c;
        this.a32 = b;
        this.a33 = a;
        this.transformPoints1 = function (w) {
            var t = w.length;
            var A = this.a11;
            var z = this.a12;
            var v = this.a13;
            var r = this.a21;
            var q = this.a22;
            var o = this.a23;
            var m = this.a31;
            var k = this.a32;
            var j = this.a33;
            for (var n = 0; n < t; n += 2) {
                var u = w[n];
                var s = w[n + 1];
                var l = v * u + o * s + j;
                w[n] = (A * u + r * s + m) / l;
                w[n + 1] = (z * u + q * s + k) / l
            }
        };
        this.transformPoints2 = function (m, k) {
            var r = m.length;
            for (var l = 0; l < r; l++) {
                var j = m[l];
                var q = k[l];
                var o = this.a13 * j + this.a23 * q + this.a33;
                m[l] = (this.a11 * j + this.a21 * q + this.a31) / o;
                k[l] = (this.a12 * j + this.a22 * q + this.a32) / o
            }
        };
        this.buildAdjoint = function () {
            return new PerspectiveTransform(this.a22 * this.a33 - this.a23 * this.a32, this.a23 * this.a31 - this.a21 * this.a33, this.a21 * this.a32 - this.a22 * this.a31, this.a13 * this.a32 - this.a12 * this.a33, this.a11 * this.a33 - this.a13 * this.a31, this.a12 * this.a31 - this.a11 * this.a32, this.a12 * this.a23 - this.a13 * this.a22, this.a13 * this.a21 - this.a11 * this.a23, this.a11 * this.a22 - this.a12 * this.a21)
        };
        this.times = function (j) {
            return new PerspectiveTransform(this.a11 * j.a11 + this.a21 * j.a12 + this.a31 * j.a13, this.a11 * j.a21 + this.a21 * j.a22 + this.a31 * j.a23, this.a11 * j.a31 + this.a21 * j.a32 + this.a31 * j.a33, this.a12 * j.a11 + this.a22 * j.a12 + this.a32 * j.a13, this.a12 * j.a21 + this.a22 * j.a22 + this.a32 * j.a23, this.a12 * j.a31 + this.a22 * j.a32 + this.a32 * j.a33, this.a13 * j.a11 + this.a23 * j.a12 + this.a33 * j.a13, this.a13 * j.a21 + this.a23 * j.a22 + this.a33 * j.a23, this.a13 * j.a31 + this.a23 * j.a32 + this.a33 * j.a33)
        }
    }

    PerspectiveTransform.quadrilateralToQuadrilateral = function (q, e, o, d, n, c, m, b, h, r, l, f, a, j, i, s) {
        var g = this.quadrilateralToSquare(q, e, o, d, n, c, m, b);
        var k = this.squareToQuadrilateral(h, r, l, f, a, j, i, s);
        return k.times(g)
    };
    PerspectiveTransform.squareToQuadrilateral = function (f, h, d, g, b, e, a, c) {
        var dx1, dx2, dx3,
            dy1, dy2, dy3,
            a13, a23,
            denominator;
        dy2 = c - e;
        dy3 = h - g + e - c;
        if (dy2 == 0 && dy3 == 0) {
            return new PerspectiveTransform(d - f, b - d, f, g - h, e - g, h, 0, 0, 1)
        } else {
            dx1 = d - b;
            dx2 = a - b;
            dx3 = f - d + b - a;
            dy1 = g - e;
            denominator = dx1 * dy2 - dx2 * dy1;
            a13 = (dx3 * dy2 - dx2 * dy3) / denominator;
            a23 = (dx1 * dy3 - dx3 * dy1) / denominator;
            return new PerspectiveTransform(d - f + a13 * d, a - f + a23 * a, f, g - h + a13 * g, c - h + a23 * c, h, a13, a23, 1)
        }
    };
    PerspectiveTransform.quadrilateralToSquare = function (f, h, d, g, b, e, a, c) {
        return this.squareToQuadrilateral(f, h, d, g, b, e, a, c).buildAdjoint()
    };
    function DetectorResult(b, a) {
        this.bits = b;
        this.points = a
    }

    function Detector(a) {
        this.image = a;
        this.resultPointCallback = null;
        this.sizeOfBlackWhiteBlackRun = function (m, l, c, b) {
            var d = Math.abs(b - l) > Math.abs(c - m);
            if (d) {
                var s = m;
                m = l;
                l = s;
                s = c;
                c = b;
                b = s
            }
            var j = Math.abs(c - m);
            var i = Math.abs(b - l);
            var q = -j >> 1;
            var v = l < b ? 1 : -1;
            var f = m < c ? 1 : -1;
            var e = 0;
            for (var h = m, g = l; h != c; h += f) {
                var u = d ? g : h;
                var t = d ? h : g;
                if (e == 1) {
                    if (this.image[u + t * qrcode.width]) {
                        e++
                    }
                } else {
                    if (!this.image[u + t * qrcode.width]) {
                        e++
                    }
                }
                if (e == 3) {
                    var o = h - m;
                    var n = g - l;
                    return Math.sqrt((o * o + n * n))
                }
                q += i;
                if (q > 0) {
                    if (g == b) {
                        break
                    }
                    g += v;
                    q -= j
                }
            }
            var k = c - m;
            var r = b - l;
            return Math.sqrt((k * k + r * r))
        };
        this.sizeOfBlackWhiteBlackRunBothWays = function (i, g, h, f) {
            var b = this.sizeOfBlackWhiteBlackRun(i, g, h, f);
            var e = 1;
            var d = i - (h - i);
            if (d < 0) {
                e = i / (i - d);
                d = 0
            } else {
                if (d >= qrcode.width) {
                    e = (qrcode.width - 1 - i) / (d - i);
                    d = qrcode.width - 1
                }
            }
            var c = Math.floor(g - (f - g) * e);
            e = 1;
            if (c < 0) {
                e = g / (g - c);
                c = 0
            } else {
                if (c >= qrcode.height) {
                    e = (qrcode.height - 1 - g) / (c - g);
                    c = qrcode.height - 1
                }
            }
            d = Math.floor(i + (d - i) * e);
            b += this.sizeOfBlackWhiteBlackRun(i, g, d, c);
            return b - 1
        };
        this.calculateModuleSizeOneWay = function (c, d) {
            var b = this.sizeOfBlackWhiteBlackRunBothWays(Math.floor(c.X), Math.floor(c.Y), Math.floor(d.X), Math.floor(d.Y));
            var e = this.sizeOfBlackWhiteBlackRunBothWays(Math.floor(d.X), Math.floor(d.Y), Math.floor(c.X), Math.floor(c.Y));
            if (isNaN(b)) {
                return e / 7
            }
            if (isNaN(e)) {
                return b / 7
            }
            return(b + e) / 14
        };
        this.calculateModuleSize = function (d, c, b) {
            return(this.calculateModuleSizeOneWay(d, c) + this.calculateModuleSizeOneWay(d, b)) / 2
        };
        this.distance = function (c, b) {
            var xDiff = c.X - b.X;
            var yDiff = c.Y - b.Y;
            return Math.sqrt((xDiff * xDiff + yDiff * yDiff))
        };
        this.computeDimension = function (g, f, d, e) {
            var b = Math.round(this.distance(g, f) / e);
            var c = Math.round(this.distance(g, d) / e);
            var h = ((b + c) >> 1) + 7;
            switch (h & 3) {
                case 0:
                    h++;
                    break;
                case 2:
                    h--;
                    break;
                case 3:
                    throw"Error"
            }
            return h
        };
        this.findAlignmentInRegion = function (g, f, d, j) {
            var k = Math.floor(j * g);
            var h = Math.max(0, f - k);
            var i = Math.min(qrcode.width - 1, f + k);
            if (i - h < g * 3) {
                throw"Error"
            }
            var b = Math.max(0, d - k);
            var c = Math.min(qrcode.height - 1, d + k);
            var e = new AlignmentPatternFinder(this.image, h, b, i - h, c - b, g, this.resultPointCallback);
            return e.find()
        };
        this.createTransform = function (l, h, k, b, g) {
            var j = g - 3.5;
            var i;
            var f;
            var e;
            var c;
            if (b != null) {
                i = b.X;
                f = b.Y;
                e = c = j - 3
            } else {
                i = (h.X - l.X) + k.X;
                f = (h.Y - l.Y) + k.Y;
                e = c = j
            }
            var d = PerspectiveTransform.quadrilateralToQuadrilateral(3.5, 3.5, j, 3.5, e, c, 3.5, j, l.X, l.Y, h.X, h.Y, i, f, k.X, k.Y);
            return d
        };
        this.sampleGrid = function (e, b, d) {
            var c = GridSampler;
            return c.sampleGrid3(e, d, b)
        };
        this.processFinderPatternInfo = function (r) {
            var j = r.TopLeft;
            var h = r.TopRight;
            var n = r.BottomLeft;
            var d = this.calculateModuleSize(j, h, n);
            if (d < 1) {
                throw"Error"
            }
            var s = this.computeDimension(j, h, n, d);
            var b = Version.getProvisionalVersionForDimension(s);
            var k = b.DimensionForVersion - 7;
            var l = null;
            if (b.AlignmentPatternCenters.length > 0) {
                var f = h.X - j.X + n.X;
                var e = h.Y - j.Y + n.Y;
                var c = 1 - 3 / k;
                var u = Math.floor(j.X + c * (f - j.X));
                var t = Math.floor(j.Y + c * (e - j.Y));
                for (var q = 4; q <= 16; q <<= 1) {
                    l = this.findAlignmentInRegion(d, u, t, q);
                    break
                }
            }
            var g = this.createTransform(j, h, n, l, s);
            var m = this.sampleGrid(this.image, g, s);
            var o;
            if (l == null) {
                o = new Array(n, j, h)
            } else {
                o = new Array(n, j, h, l)
            }
            return new DetectorResult(m, o)
        };
        this.detect = function () {
            var b = new FinderPatternFinder().findFinderPattern(this.image);
            return this.processFinderPatternInfo(b)
        }
    }

    var FORMAT_INFO_MASK_QR = 21522;
    var FORMAT_INFO_DECODE_LOOKUP = new Array(new Array(21522, 0), new Array(20773, 1), new Array(24188, 2), new Array(23371, 3), new Array(17913, 4), new Array(16590, 5), new Array(20375, 6), new Array(19104, 7), new Array(30660, 8), new Array(29427, 9), new Array(32170, 10), new Array(30877, 11), new Array(26159, 12), new Array(25368, 13), new Array(27713, 14), new Array(26998, 15), new Array(5769, 16), new Array(5054, 17), new Array(7399, 18), new Array(6608, 19), new Array(1890, 20), new Array(597, 21), new Array(3340, 22), new Array(2107, 23), new Array(13663, 24), new Array(12392, 25), new Array(16177, 26), new Array(14854, 27), new Array(9396, 28), new Array(8579, 29), new Array(11994, 30), new Array(11245, 31));
    var BITS_SET_IN_HALF_BYTE = new Array(0, 1, 1, 2, 1, 2, 2, 3, 1, 2, 2, 3, 2, 3, 3, 4);

    function FormatInformation(a) {
        this.errorCorrectionLevel = ErrorCorrectionLevel.forBits((a >> 3) & 3);
        this.dataMask = (a & 7);
        this.__defineGetter__("ErrorCorrectionLevel", function () {
            return this.errorCorrectionLevel
        });
        this.__defineGetter__("DataMask", function () {
            return this.dataMask
        });
        this.GetHashCode = function () {
            return(this.errorCorrectionLevel.ordinal() << 3) | dataMask
        };
        this.Equals = function (c) {
            var b = c;
            return this.errorCorrectionLevel == b.errorCorrectionLevel && this.dataMask == b.dataMask
        }
    }

    FormatInformation.numBitsDiffering = function (d, c) {
        d ^= c;
        return BITS_SET_IN_HALF_BYTE[d & 15] + BITS_SET_IN_HALF_BYTE[(URShift(d, 4) & 15)] + BITS_SET_IN_HALF_BYTE[(URShift(d, 8) & 15)] + BITS_SET_IN_HALF_BYTE[(URShift(d, 12) & 15)] + BITS_SET_IN_HALF_BYTE[(URShift(d, 16) & 15)] + BITS_SET_IN_HALF_BYTE[(URShift(d, 20) & 15)] + BITS_SET_IN_HALF_BYTE[(URShift(d, 24) & 15)] + BITS_SET_IN_HALF_BYTE[(URShift(d, 28) & 15)]
    };
    FormatInformation.decodeFormatInformation = function (a) {
        var b = FormatInformation.doDecodeFormatInformation(a);
        if (b != null) {
            return b
        }
        return FormatInformation.doDecodeFormatInformation(a ^ FORMAT_INFO_MASK_QR)
    };
    FormatInformation.doDecodeFormatInformation = function (d) {
        var b = 4294967295;
        var a = 0;
        for (var c = 0; c < FORMAT_INFO_DECODE_LOOKUP.length; c++) {
            var g = FORMAT_INFO_DECODE_LOOKUP[c];
            var f = g[0];
            if (f == d) {
                return new FormatInformation(g[1])
            }
            var e = this.numBitsDiffering(d, f);
            if (e < b) {
                a = g[1];
                b = e
            }
        }
        if (b <= 3) {
            return new FormatInformation(a)
        }
        return null
    };
    function ErrorCorrectionLevel(a, c, b) {
        this.ordinal_Renamed_Field = a;
        this.bits = c;
        this.name = b;
        this.__defineGetter__("Bits", function () {
            return this.bits
        });
        this.__defineGetter__("Name", function () {
            return this.name
        });
        this.ordinal = function () {
            return this.ordinal_Renamed_Field
        }
    }

    ErrorCorrectionLevel.forBits = function (a) {
        if (a < 0 || a >= FOR_BITS.Length) {
            throw"ArgumentException"
        }
        return FOR_BITS[a]
    };
    var L = new ErrorCorrectionLevel(0, 1, "L");
    var M = new ErrorCorrectionLevel(1, 0, "M");
    var Q = new ErrorCorrectionLevel(2, 3, "Q");
    var H = new ErrorCorrectionLevel(3, 2, "H");
    var FOR_BITS = new Array(M, L, H, Q);

    function BitMatrix(d, a) {
        if (!a) {
            a = d
        }
        if (d < 1 || a < 1) {
            throw"Both dimensions must be greater than 0"
        }
        this.width = d;
        this.height = a;
        var c = d >> 5;
        if ((d & 31) != 0) {
            c++
        }
        this.rowSize = c;
        this.bits = new Array(c * a);
        for (var b = 0; b < this.bits.length; b++) {
            this.bits[b] = 0
        }
        this.__defineGetter__("Width", function () {
            return this.width
        });
        this.__defineGetter__("Height", function () {
            return this.height
        });
        this.__defineGetter__("Dimension", function () {
            if (this.width != this.height) {
                throw"Can't call getDimension() on a non-square matrix"
            }
            return this.width
        });
        this.get_Renamed = function (e, g) {
            var f = g * this.rowSize + (e >> 5);
            return((URShift(this.bits[f], (e & 31))) & 1) != 0
        };
        this.set_Renamed = function (e, g) {
            var f = g * this.rowSize + (e >> 5);
            this.bits[f] |= 1 << (e & 31)
        };
        this.flip = function (e, g) {
            var f = g * this.rowSize + (e >> 5);
            this.bits[f] ^= 1 << (e & 31)
        };
        this.clear = function () {
            var e = this.bits.length;
            for (var f = 0; f < e; f++) {
                this.bits[f] = 0
            }
        };
        this.setRegion = function (g, j, f, m) {
            if (j < 0 || g < 0) {
                throw"Left and top must be nonnegative"
            }
            if (m < 1 || f < 1) {
                throw"Height and width must be at least 1"
            }
            var l = g + f;
            var e = j + m;
            if (e > this.height || l > this.width) {
                throw"The region must fit inside the matrix"
            }
            for (var i = j; i < e; i++) {
                var h = i * this.rowSize;
                for (var k = g; k < l; k++) {
                    this.bits[h + (k >> 5)] |= 1 << (k & 31)
                }
            }
        }
    }

    function DataBlock(a, b) {
        this.numDataCodewords = a;
        this.codewords = b;
        this.__defineGetter__("NumDataCodewords", function () {
            return this.numDataCodewords
        });
        this.__defineGetter__("Codewords", function () {
            return this.codewords
        })
    }

    DataBlock.getDataBlocks = function (c, h, s) {
        if (c.length != h.TotalCodewords) {
            throw"ArgumentException"
        }
        var k = h.getECBlocksForLevel(s);
        var e = 0;
        var d = k.getECBlocks();
        for (var r = 0; r < d.length; r++) {
            e += d[r].Count
        }
        var l = new Array(e);
        var n = 0;
        for (var o = 0; o < d.length; o++) {
            var f = d[o];
            for (var r = 0; r < f.Count; r++) {
                var m = f.DataCodewords;
                var t = k.ECCodewordsPerBlock + m;
                l[n++] = new DataBlock(m, new Array(t))
            }
        }
        var u = l[0].codewords.length;
        var b = l.length - 1;
        while (b >= 0) {
            var w = l[b].codewords.length;
            if (w == u) {
                break
            }
            b--
        }
        b++;
        var g = u - k.ECCodewordsPerBlock;
        var a = 0;
        for (var r = 0; r < g; r++) {
            for (var o = 0; o < n; o++) {
                l[o].codewords[r] = c[a++]
            }
        }
        for (var o = b; o < n; o++) {
            l[o].codewords[g] = c[a++]
        }
        var q = l[0].codewords.length;
        for (var r = g; r < q; r++) {
            for (var o = 0; o < n; o++) {
                var v = o < b ? r : r + 1;
                l[o].codewords[v] = c[a++]
            }
        }
        return l
    };
    function BitMatrixParser(a) {
        var b = a.Dimension;
        if (b < 21 || (b & 3) != 1) {
            throw"Error BitMatrixParser"
        }
        this.bitMatrix = a;
        this.parsedVersion = null;
        this.parsedFormatInfo = null;
        this.copyBit = function (d, c, e) {
            return this.bitMatrix.get_Renamed(d, c) ? (e << 1) | 1 : e << 1
        };
        this.readFormatInformation = function () {
            if (this.parsedFormatInfo != null) {
                return this.parsedFormatInfo
            }
            var g = 0;
            for (var e = 0; e < 6; e++) {
                g = this.copyBit(e, 8, g)
            }
            g = this.copyBit(7, 8, g);
            g = this.copyBit(8, 8, g);
            g = this.copyBit(8, 7, g);
            for (var c = 5; c >= 0; c--) {
                g = this.copyBit(8, c, g)
            }
            this.parsedFormatInfo = FormatInformation.decodeFormatInformation(g);
            if (this.parsedFormatInfo != null) {
                return this.parsedFormatInfo
            }
            var f = this.bitMatrix.Dimension;
            g = 0;
            var d = f - 8;
            for (var e = f - 1; e >= d; e--) {
                g = this.copyBit(e, 8, g)
            }
            for (var c = f - 7; c < f; c++) {
                g = this.copyBit(8, c, g)
            }
            this.parsedFormatInfo = FormatInformation.decodeFormatInformation(g);
            if (this.parsedFormatInfo != null) {
                return this.parsedFormatInfo
            }
            throw"Error readFormatInformation"
        };
        this.readVersion = function () {
            if (this.parsedVersion != null) {
                return this.parsedVersion
            }
            var h = this.bitMatrix.Dimension;
            var f = (h - 17) >> 2;
            if (f <= 6) {
                return Version.getVersionForNumber(f)
            }
            var g = 0;
            var e = h - 11;
            for (var c = 5; c >= 0; c--) {
                for (var d = h - 9; d >= e; d--) {
                    g = this.copyBit(d, c, g)
                }
            }
            this.parsedVersion = Version.decodeVersionInformation(g);
            if (this.parsedVersion != null && this.parsedVersion.DimensionForVersion == h) {
                return this.parsedVersion
            }
            g = 0;
            for (var d = 5; d >= 0; d--) {
                for (var c = h - 9; c >= e; c--) {
                    g = this.copyBit(d, c, g)
                }
            }
            this.parsedVersion = Version.decodeVersionInformation(g);
            if (this.parsedVersion != null && this.parsedVersion.DimensionForVersion == h) {
                return this.parsedVersion
            }
            throw"Error readVersion"
        };
        this.readCodewords = function () {
            var r = this.readFormatInformation();
            var o = this.readVersion();
            var c = DataMask.forReference(r.DataMask);
            var f = this.bitMatrix.Dimension;
            c.unmaskBitMatrix(this.bitMatrix, f);
            var k = o.buildFunctionPattern();
            var n = true;
            var s = new Array(o.TotalCodewords);
            var m = 0;
            var q = 0;
            var h = 0;
            for (var e = f - 1; e > 0; e -= 2) {
                if (e == 6) {
                    e--
                }
                for (var l = 0; l < f; l++) {
                    var g = n ? f - 1 - l : l;
                    for (var d = 0; d < 2; d++) {
                        if (!k.get_Renamed(e - d, g)) {
                            h++;
                            q <<= 1;
                            if (this.bitMatrix.get_Renamed(e - d, g)) {
                                q |= 1
                            }
                            if (h == 8) {
                                s[m++] = q;
                                h = 0;
                                q = 0
                            }
                        }
                    }
                }
                n ^= true
            }
            if (m != o.TotalCodewords) {
                throw"Error readCodewords"
            }
            return s
        }
    }

    var DataMask = {};
    DataMask.forReference = function (a) {
        if (a < 0 || a > 7) {
            throw"System.ArgumentException"
        }
        return DataMask.DATA_MASKS[a]
    };
    function DataMask000() {
        this.unmaskBitMatrix = function (c, d) {
            for (var b = 0; b < d; b++) {
                for (var a = 0; a < d; a++) {
                    if (this.isMasked(b, a)) {
                        c.flip(a, b)
                    }
                }
            }
        };
        this.isMasked = function (b, a) {
            return((b + a) & 1) == 0
        }
    }

    function DataMask001() {
        this.unmaskBitMatrix = function (c, d) {
            for (var b = 0; b < d; b++) {
                for (var a = 0; a < d; a++) {
                    if (this.isMasked(b, a)) {
                        c.flip(a, b)
                    }
                }
            }
        };
        this.isMasked = function (b, a) {
            return(b & 1) == 0
        }
    }

    function DataMask010() {
        this.unmaskBitMatrix = function (c, d) {
            for (var b = 0; b < d; b++) {
                for (var a = 0; a < d; a++) {
                    if (this.isMasked(b, a)) {
                        c.flip(a, b)
                    }
                }
            }
        };
        this.isMasked = function (b, a) {
            return a % 3 == 0
        }
    }

    function DataMask011() {
        this.unmaskBitMatrix = function (c, d) {
            for (var b = 0; b < d; b++) {
                for (var a = 0; a < d; a++) {
                    if (this.isMasked(b, a)) {
                        c.flip(a, b)
                    }
                }
            }
        };
        this.isMasked = function (b, a) {
            return(b + a) % 3 == 0
        }
    }

    function DataMask100() {
        this.unmaskBitMatrix = function (c, d) {
            for (var b = 0; b < d; b++) {
                for (var a = 0; a < d; a++) {
                    if (this.isMasked(b, a)) {
                        c.flip(a, b)
                    }
                }
            }
        };
        this.isMasked = function (b, a) {
            return(((URShift(b, 1)) + (a / 3)) & 1) == 0
        }
    }

    function DataMask101() {
        this.unmaskBitMatrix = function (c, d) {
            for (var b = 0; b < d; b++) {
                for (var a = 0; a < d; a++) {
                    if (this.isMasked(b, a)) {
                        c.flip(a, b)
                    }
                }
            }
        };
        this.isMasked = function (c, b) {
            var a = c * b;
            return(a & 1) + (a % 3) == 0
        }
    }

    function DataMask110() {
        this.unmaskBitMatrix = function (c, d) {
            for (var b = 0; b < d; b++) {
                for (var a = 0; a < d; a++) {
                    if (this.isMasked(b, a)) {
                        c.flip(a, b)
                    }
                }
            }
        };
        this.isMasked = function (c, b) {
            var a = c * b;
            return(((a & 1) + (a % 3)) & 1) == 0
        }
    }

    function DataMask111() {
        this.unmaskBitMatrix = function (c, d) {
            for (var b = 0; b < d; b++) {
                for (var a = 0; a < d; a++) {
                    if (this.isMasked(b, a)) {
                        c.flip(a, b)
                    }
                }
            }
        };
        this.isMasked = function (b, a) {
            return((((b + a) & 1) + ((b * a) % 3)) & 1) == 0
        }
    }

    DataMask.DATA_MASKS = new Array(new DataMask000(), new DataMask001(), new DataMask010(), new DataMask011(), new DataMask100(), new DataMask101(), new DataMask110(), new DataMask111());
    function ReedSolomonDecoder(field) {
        this.field = field;
        this.decode = function (received, twoS) {
            var poly = new GF256Poly(this.field, received);
            var i;
            var syndromeCoefficients = new Array(twoS);
            for (i = 0; i < syndromeCoefficients.length; i++) {
                syndromeCoefficients[i] = 0
            }
            var dataMatrix = false;
            var noError = true;
            for (i = 0; i < twoS; i++) {
                var _eval = poly.evaluateAt(this.field.exp(dataMatrix ? i + 1 : i));
                syndromeCoefficients[syndromeCoefficients.length - 1 - i] = _eval;
                if (_eval != 0) {
                    noError = false
                }
            }
            if (noError) {
                return
            }
            var syndrome = new GF256Poly(this.field, syndromeCoefficients);
            var sigmaOmega = this.runEuclideanAlgorithm(this.field.buildMonomial(twoS, 1), syndrome, twoS);
            var sigma = sigmaOmega[0];
            var omega = sigmaOmega[1];
            var errorLocations = this.findErrorLocations(sigma);
            var errorMagnitudes = this.findErrorMagnitudes(omega, errorLocations, dataMatrix);
            for (i = 0; i < errorLocations.length; i++) {
                var position = received.length - 1 - this.field.log(errorLocations[i]);
                if (position < 0) {
                    throw"ReedSolomonException Bad error location"
                }
                received[position] = GF256.addOrSubtract(received[position], errorMagnitudes[i])
            }
        };
        this.runEuclideanAlgorithm = function (a, b, R) {
            if (a.Degree < b.Degree) {
                var temp = a;
                a = b;
                b = temp
            }
            var rLast = a;
            var r = b;
            var sLast = this.field.One;
            var s = this.field.Zero;
            var tLast = this.field.Zero;
            var t = this.field.One;
            while (r.Degree >= Math.floor(R / 2)) {
                var rLastLast = rLast;
                var sLastLast = sLast;
                var tLastLast = tLast;
                rLast = r;
                sLast = s;
                tLast = t;
                if (rLast.Zero) {
                    throw"r_{i-1} was zero"
                }
                r = rLastLast;
                var q = this.field.Zero;
                var denominatorLeadingTerm = rLast.getCoefficient(rLast.Degree);
                var dltInverse = this.field.inverse(denominatorLeadingTerm);
                while (r.Degree >= rLast.Degree && !r.Zero) {
                    var degreeDiff = r.Degree - rLast.Degree;
                    var scale = this.field.multiply(r.getCoefficient(r.Degree), dltInverse);
                    q = q.addOrSubtract(this.field.buildMonomial(degreeDiff, scale));
                    r = r.addOrSubtract(rLast.multiplyByMonomial(degreeDiff, scale))
                }
                s = q.multiply1(sLast).addOrSubtract(sLastLast);
                t = q.multiply1(tLast).addOrSubtract(tLastLast)
            }
            var sigmaTildeAtZero = t.getCoefficient(0);
            if (sigmaTildeAtZero == 0) {
                throw"ReedSolomonException sigmaTilde(0) was zero"
            }
            var inverse = this.field.inverse(sigmaTildeAtZero);
            var sigma = t.multiply2(inverse);
            var omega = r.multiply2(inverse);
            return new Array(sigma, omega)
        };
        this.findErrorLocations = function (errorLocator) {
            var numErrors = errorLocator.Degree;
            if (numErrors == 1) {
                return new Array(errorLocator.getCoefficient(1))
            }
            var result = new Array(numErrors);
            var e = 0;
            for (var i = 1; i < 256 && e < numErrors; i++) {
                if (errorLocator.evaluateAt(i) == 0) {
                    result[e] = this.field.inverse(i);
                    e++
                }
            }
            if (e != numErrors) {
                throw"Error locator degree does not match number of roots"
            }
            return result
        };
        this.findErrorMagnitudes = function (errorEvaluator, errorLocations, dataMatrix) {
            var s = errorLocations.length;
            var result = new Array(s);
            for (var i = 0; i < s; i++) {
                var xiInverse = this.field.inverse(errorLocations[i]);
                var denominator = 1;
                for (var j = 0; j < s; j++) {
                    if (i != j) {
                        denominator = this.field.multiply(denominator, GF256.addOrSubtract(1, this.field.multiply(errorLocations[j], xiInverse)))
                    }
                }
                result[i] = this.field.multiply(errorEvaluator.evaluateAt(xiInverse), this.field.inverse(denominator));
                if (dataMatrix) {
                    result[i] = this.field.multiply(result[i], xiInverse)
                }
            }
            return result
        }
    }

    function GF256Poly(f, e) {
        if (e == null || e.length == 0) {
            throw"System.ArgumentException"
        }
        this.field = f;
        var c = e.length;
        if (c > 1 && e[0] == 0) {
            var d = 1;
            while (d < c && e[d] == 0) {
                d++
            }
            if (d == c) {
                this.coefficients = f.Zero.coefficients
            } else {
                this.coefficients = new Array(c - d);
                for (var b = 0; b < this.coefficients.length; b++) {
                    this.coefficients[b] = 0
                }
                for (var a = 0; a < this.coefficients.length; a++) {
                    this.coefficients[a] = e[d + a]
                }
            }
        } else {
            this.coefficients = e
        }
        this.__defineGetter__("Zero", function () {
            return this.coefficients[0] == 0
        });
        this.__defineGetter__("Degree", function () {
            return this.coefficients.length - 1
        });
        this.__defineGetter__("Coefficients", function () {
            return this.coefficients
        });
        this.getCoefficient = function (g) {
            return this.coefficients[this.coefficients.length - 1 - g]
        };
        this.evaluateAt = function (h) {
            if (h == 0) {
                return this.getCoefficient(0)
            }
            var l = this.coefficients.length;
            if (h == 1) {
                var g = 0;
                for (var k = 0; k < l; k++) {
                    g = GF256.addOrSubtract(g, this.coefficients[k])
                }
                return g
            }
            var j = this.coefficients[0];
            for (var k = 1; k < l; k++) {
                j = GF256.addOrSubtract(this.field.multiply(h, j), this.coefficients[k])
            }
            return j
        };
        this.addOrSubtract = function (g) {
            if (this.field != g.field) {
                throw"GF256Polys do not have same GF256 field"
            }
            if (this.Zero) {
                return g
            }
            if (g.Zero) {
                return this
            }
            var o = this.coefficients;
            var n = g.coefficients;
            if (o.length > n.length) {
                var j = o;
                o = n;
                n = j
            }
            var h = new Array(n.length);
            var k = n.length - o.length;
            for (var m = 0; m < k; m++) {
                h[m] = n[m]
            }
            for (var l = k; l < n.length; l++) {
                h[l] = GF256.addOrSubtract(o[l - k], n[l])
            }
            return new GF256Poly(f, h)
        };
        this.multiply1 = function (o) {
            if (this.field != o.field) {
                throw"GF256Polys do not have same GF256 field"
            }
            if (this.Zero || o.Zero) {
                return this.field.Zero
            }
            var r = this.coefficients;
            var g = r.length;
            var l = o.coefficients;
            var n = l.length;
            var q = new Array(g + n - 1);
            for (var m = 0; m < g; m++) {
                var h = r[m];
                for (var k = 0; k < n; k++) {
                    q[m + k] = GF256.addOrSubtract(q[m + k], this.field.multiply(h, l[k]))
                }
            }
            return new GF256Poly(this.field, q)
        };
        this.multiply2 = function (g) {
            if (g == 0) {
                return this.field.Zero
            }
            if (g == 1) {
                return this
            }
            var j = this.coefficients.length;
            var k = new Array(j);
            for (var h = 0; h < j; h++) {
                k[h] = this.field.multiply(this.coefficients[h], g)
            }
            return new GF256Poly(this.field, k)
        };
        this.multiplyByMonomial = function (l, g) {
            if (l < 0) {
                throw"System.ArgumentException"
            }
            if (g == 0) {
                return this.field.Zero
            }
            var j = this.coefficients.length;
            var k = new Array(j + l);
            for (var h = 0; h < k.length; h++) {
                k[h] = 0
            }
            for (var h = 0; h < j; h++) {
                k[h] = this.field.multiply(this.coefficients[h], g)
            }
            return new GF256Poly(this.field, k)
        };
        this.divide = function (l) {
            if (this.field != l.field) {
                throw"GF256Polys do not have same GF256 field"
            }
            if (l.Zero) {
                throw"Divide by 0"
            }
            var j = this.field.Zero;
            var o = this;
            var g = l.getCoefficient(l.Degree);
            var n = this.field.inverse(g);
            while (o.Degree >= l.Degree && !o.Zero) {
                var m = o.Degree - l.Degree;
                var h = this.field.multiply(o.getCoefficient(o.Degree), n);
                var i = l.multiplyByMonomial(m, h);
                var k = this.field.buildMonomial(m, h);
                j = j.addOrSubtract(k);
                o = o.addOrSubtract(i)
            }
            return new Array(j, o)
        }
    }

    function GF256(b) {
        this.expTable = new Array(256);
        this.logTable = new Array(256);
        var a = 1;
        for (var e = 0; e < 256; e++) {
            this.expTable[e] = a;
            a <<= 1;
            if (a >= 256) {
                a ^= b
            }
        }
        for (var e = 0; e < 255; e++) {
            this.logTable[this.expTable[e]] = e
        }
        var d = new Array(1);
        d[0] = 0;
        this.zero = new GF256Poly(this, new Array(d));
        var c = new Array(1);
        c[0] = 1;
        this.one = new GF256Poly(this, new Array(c));
        this.__defineGetter__("Zero", function () {
            return this.zero
        });
        this.__defineGetter__("One", function () {
            return this.one
        });
        this.buildMonomial = function (j, f) {
            if (j < 0) {
                throw"System.ArgumentException"
            }
            if (f == 0) {
                return zero
            }
            var h = new Array(j + 1);
            for (var g = 0; g < h.length; g++) {
                h[g] = 0
            }
            h[0] = f;
            return new GF256Poly(this, h)
        };
        this.exp = function (f) {
            return this.expTable[f]
        };
        this.log = function (f) {
            if (f == 0) {
                throw"System.ArgumentException"
            }
            return this.logTable[f]
        };
        this.inverse = function (f) {
            if (f == 0) {
                throw"System.ArithmeticException"
            }
            return this.expTable[255 - this.logTable[f]]
        };
        this.multiply = function (g, f) {
            if (g == 0 || f == 0) {
                return 0
            }
            if (g == 1) {
                return f
            }
            if (f == 1) {
                return g
            }
            return this.expTable[(this.logTable[g] + this.logTable[f]) % 255]
        }
    }

    GF256.QR_CODE_FIELD = new GF256(285);
    GF256.DATA_MATRIX_FIELD = new GF256(301);
    GF256.addOrSubtract = function (d, c) {
        return d ^ c
    };
    var Decoder = {};
    Decoder.rsDecoder = new ReedSolomonDecoder(GF256.QR_CODE_FIELD);
    Decoder.correctErrors = function (g, b) {
        var d = g.length;
        var f = new Array(d);
        for (var e = 0; e < d; e++) {
            f[e] = g[e] & 255
        }
        var a = g.length - b;
        try {
            Decoder.rsDecoder.decode(f, a)
        } catch (c) {
            throw c
        }
        for (var e = 0; e < b; e++) {
            g[e] = f[e]
        }
    };
    Decoder.decode = function (r) {
        var b = new BitMatrixParser(r);
        var o = b.readVersion();
        var c = b.readFormatInformation().ErrorCorrectionLevel;
        var q = b.readCodewords();
        var a = DataBlock.getDataBlocks(q, o, c);
        var f = 0;
        for (var k = 0; k < a.Length; k++) {
            f += a[k].NumDataCodewords
        }
        var e = new Array(f);
        var n = 0;
        for (var h = 0; h < a.length; h++) {
            var m = a[h];
            var d = m.Codewords;
            var g = m.NumDataCodewords;
            Decoder.correctErrors(d, g);
            for (var k = 0; k < g; k++) {
                e[n++] = d[k]
            }
        }
        var l = new QRCodeDataBlockReader(e, o.VersionNumber, c.Bits);
        return l
    };




    var qrcode = {};
    qrcode.imagedata = null;
    qrcode.width = 0;
    qrcode.height = 0;
    qrcode.qrCodeSymbol = null;
    qrcode.sizeOfDataLengthInfo = [
        [10, 9, 8, 8],
        [12, 11, 16, 10],
        [14, 13, 16, 12]
    ];
    qrcode.callback = null;


    qrcode.decode = function (canvas) {
        var context = canvas.getContext("2d");
        qrcode.width = canvas.width;
        qrcode.height = canvas.height;
        qrcode.imagedata = context.getImageData(0, 0, qrcode.width, qrcode.height);
        return qrcode.process(context);
    };

    qrcode.process = function (n) {
        var c = qrcode.grayScaleToBitmap(qrcode.grayscale());
        var h = new Detector(c);
        var m = h.detect();
        n.putImageData(qrcode.imagedata, 0, 0);
        var k = Decoder.decode(m.bits);
        var g = k.DataByte;
        var l = "";
        for (var f = 0; f < g.length; f++) {
            for (var e = 0; e < g[f].length; e++) {
                l += String.fromCharCode(g[f][e])
            }
        }
        return l
    };
    
    qrcode.getPixel = function (a, b) {
        if (qrcode.width < a) {
            throw"point error"
        }
        if (qrcode.height < b) {
            throw"point error"
        }
        var point = (a * 4) + (b * qrcode.width * 4);
        var p = (qrcode.imagedata.data[point] * 30 + qrcode.imagedata.data[point + 1] * 59 + qrcode.imagedata.data[point + 2] * 11) / 100;
        return p
    };
    qrcode.binarize = function (d) {
        var c = new Array(qrcode.width * qrcode.height);
        for (var e = 0; e < qrcode.height; e++) {
            for (var b = 0; b < qrcode.width; b++) {
                var a = qrcode.getPixel(b, e);
                c[b + e * qrcode.width] = a <= d ? true : false
            }
        }
        return c
    };
    qrcode.getMiddleBrightnessPerArea = function (d) {
        var c = 4;
        var k = Math.floor(qrcode.width / c);
        var j = Math.floor(qrcode.height / c);
        var f = new Array(c);
        for (var g = 0; g < c; g++) {
            f[g] = new Array(c);
            for (var e = 0; e < c; e++) {
                f[g][e] = new Array(0, 0)
            }
        }
        for (var o = 0; o < c; o++) {
            for (var a = 0; a < c; a++) {
                f[a][o][0] = 255;
                for (var l = 0; l < j; l++) {
                    for (var n = 0; n < k; n++) {
                        var h = d[k * a + n + (j * o + l) * qrcode.width];
                        if (h < f[a][o][0]) {
                            f[a][o][0] = h
                        }
                        if (h > f[a][o][1]) {
                            f[a][o][1] = h
                        }
                    }
                }
            }
        }
        var m = new Array(c);
        for (var b = 0; b < c; b++) {
            m[b] = new Array(c)
        }
        for (var o = 0; o < c; o++) {
            for (var a = 0; a < c; a++) {
                m[a][o] = Math.floor((f[a][o][0] + f[a][o][1]) / 2)
            }
        }
        return m
    };
    qrcode.grayScaleToBitmap = function (f) {
        var j = qrcode.getMiddleBrightnessPerArea(f);
        var b = j.length;
        var e = Math.floor(qrcode.width / b);
        var d = Math.floor(qrcode.height / b);
        var c = new Array(qrcode.height * qrcode.width);
        for (var i = 0; i < b; i++) {
            for (var a = 0; a < b; a++) {
                for (var g = 0; g < d; g++) {
                    for (var h = 0; h < e; h++) {
                        c[e * a + h + (d * i + g) * qrcode.width] = (f[e * a + h + (d * i + g) * qrcode.width] < j[a][i]) ? true : false
                    }
                }
            }
        }
        return c
    };
    qrcode.grayscale = function () {
        var c = new Array(qrcode.width * qrcode.height);
        for (var d = 0; d < qrcode.height; d++) {
            for (var b = 0; b < qrcode.width; b++) {
                var a = qrcode.getPixel(b, d);
                c[b + d * qrcode.width] = a
            }
        }
        return c
    };
    function URShift(a, b) {
        if (a >= 0) {
            return a >> b
        } else {
            return(a >> b) + (2 << ~b)
        }
    }

    Array.prototype.remove = function (c, b) {
        var a = this.slice((b || c) + 1 || this.length);
        this.length = c < 0 ? this.length + c : c;
        return this.push.apply(this, a)
    };
    var MIN_SKIP = 3;
    var MAX_MODULES = 57;
    var INTEGER_MATH_SHIFT = 8;
    var CENTER_QUORUM = 2;
    qrcode.orderBestPatterns = function (c) {
        function b(l, k) {
            var xDiff = l.X - k.X;
            var yDiff = l.Y - k.Y;
            return Math.sqrt((xDiff * xDiff + yDiff * yDiff))
        }

        function d(k, o, n) {
            var m = o.x;
            var l = o.y;
            return((n.x - m) * (k.y - l)) - ((n.y - l) * (k.x - m))
        }

        var i = b(c[0], c[1]);
        var f = b(c[1], c[2]);
        var e = b(c[0], c[2]);
        var a, j, h;
        if (f >= i && f >= e) {
            j = c[0];
            a = c[1];
            h = c[2]
        } else {
            if (e >= f && e >= i) {
                j = c[1];
                a = c[0];
                h = c[2]
            } else {
                j = c[2];
                a = c[0];
                h = c[1]
            }
        }
        if (d(a, j, h) < 0) {
            var g = a;
            a = h;
            h = g
        }
        c[0] = a;
        c[1] = j;
        c[2] = h
    };
    function FinderPattern(c, a, b) {
        this.x = c;
        this.y = a;
        this.count = 1;
        this.estimatedModuleSize = b;
        this.__defineGetter__("EstimatedModuleSize", function () {
            return this.estimatedModuleSize
        });
        this.__defineGetter__("Count", function () {
            return this.count
        });
        this.__defineGetter__("X", function () {
            return this.x
        });
        this.__defineGetter__("Y", function () {
            return this.y
        });
        this.incrementCount = function () {
            this.count++
        };
        this.aboutEquals = function (f, e, d) {
            if (Math.abs(e - this.y) <= f && Math.abs(d - this.x) <= f) {
                var g = Math.abs(f - this.estimatedModuleSize);
                return g <= 1 || g / this.estimatedModuleSize <= 1
            }
            return false
        }
    }

    function FinderPatternInfo(a) {
        this.bottomLeft = a[0];
        this.topLeft = a[1];
        this.topRight = a[2];
        this.__defineGetter__("BottomLeft", function () {
            return this.bottomLeft
        });
        this.__defineGetter__("TopLeft", function () {
            return this.topLeft
        });
        this.__defineGetter__("TopRight", function () {
            return this.topRight
        })
    }

    function FinderPatternFinder() {
        this.image = null;
        this.possibleCenters = [];
        this.hasSkipped = false;
        this.crossCheckStateCount = new Array(0, 0, 0, 0, 0);
        this.resultPointCallback = null;
        this.__defineGetter__("CrossCheckStateCount", function () {
            this.crossCheckStateCount[0] = 0;
            this.crossCheckStateCount[1] = 0;
            this.crossCheckStateCount[2] = 0;
            this.crossCheckStateCount[3] = 0;
            this.crossCheckStateCount[4] = 0;
            return this.crossCheckStateCount
        });
        this.foundPatternCross = function (f) {
            var b = 0;
            for (var d = 0; d < 5; d++) {
                var e = f[d];
                if (e == 0) {
                    return false
                }
                b += e
            }
            if (b < 7) {
                return false
            }
            var c = Math.floor((b << INTEGER_MATH_SHIFT) / 7);
            var a = Math.floor(c / 2);
            return Math.abs(c - (f[0] << INTEGER_MATH_SHIFT)) < a && Math.abs(c - (f[1] << INTEGER_MATH_SHIFT)) < a && Math.abs(3 * c - (f[2] << INTEGER_MATH_SHIFT)) < 3 * a && Math.abs(c - (f[3] << INTEGER_MATH_SHIFT)) < a && Math.abs(c - (f[4] << INTEGER_MATH_SHIFT)) < a
        };
        this.centerFromEnd = function (b, a) {
            return(a - b[4] - b[3]) - b[2] / 2
        };
        this.crossCheckVertical = function (a, j, d, g) {
            var c = this.image;
            var h = qrcode.height;
            var b = this.CrossCheckStateCount;
            var f = a;
            while (f >= 0 && c[j + f * qrcode.width]) {
                b[2]++;
                f--
            }
            if (f < 0) {
                return NaN
            }
            while (f >= 0 && !c[j + f * qrcode.width] && b[1] <= d) {
                b[1]++;
                f--
            }
            if (f < 0 || b[1] > d) {
                return NaN
            }
            while (f >= 0 && c[j + f * qrcode.width] && b[0] <= d) {
                b[0]++;
                f--
            }
            if (b[0] > d) {
                return NaN
            }
            f = a + 1;
            while (f < h && c[j + f * qrcode.width]) {
                b[2]++;
                f++
            }
            if (f == h) {
                return NaN
            }
            while (f < h && !c[j + f * qrcode.width] && b[3] < d) {
                b[3]++;
                f++
            }
            if (f == h || b[3] >= d) {
                return NaN
            }
            while (f < h && c[j + f * qrcode.width] && b[4] < d) {
                b[4]++;
                f++
            }
            if (b[4] >= d) {
                return NaN
            }
            var e = b[0] + b[1] + b[2] + b[3] + b[4];
            if (5 * Math.abs(e - g) >= 2 * g) {
                return NaN
            }
            return this.foundPatternCross(b) ? this.centerFromEnd(b, f) : NaN
        };
        this.crossCheckHorizontal = function (b, a, e, h) {
            var d = this.image;
            var i = qrcode.width;
            var c = this.CrossCheckStateCount;
            var g = b;
            while (g >= 0 && d[g + a * qrcode.width]) {
                c[2]++;
                g--
            }
            if (g < 0) {
                return NaN
            }
            while (g >= 0 && !d[g + a * qrcode.width] && c[1] <= e) {
                c[1]++;
                g--
            }
            if (g < 0 || c[1] > e) {
                return NaN
            }
            while (g >= 0 && d[g + a * qrcode.width] && c[0] <= e) {
                c[0]++;
                g--
            }
            if (c[0] > e) {
                return NaN
            }
            g = b + 1;
            while (g < i && d[g + a * qrcode.width]) {
                c[2]++;
                g++
            }
            if (g == i) {
                return NaN
            }
            while (g < i && !d[g + a * qrcode.width] && c[3] < e) {
                c[3]++;
                g++
            }
            if (g == i || c[3] >= e) {
                return NaN
            }
            while (g < i && d[g + a * qrcode.width] && c[4] < e) {
                c[4]++;
                g++
            }
            if (c[4] >= e) {
                return NaN
            }
            var f = c[0] + c[1] + c[2] + c[3] + c[4];
            if (5 * Math.abs(f - h) >= h) {
                return NaN
            }
            return this.foundPatternCross(c) ? this.centerFromEnd(c, g) : NaN
        };
        this.handlePossibleCenter = function (c, f, e) {
            var d = c[0] + c[1] + c[2] + c[3] + c[4];
            var n = this.centerFromEnd(c, e);
            var b = this.crossCheckVertical(f, Math.floor(n), c[2], d);
            if (!isNaN(b)) {
                n = this.crossCheckHorizontal(Math.floor(n), Math.floor(b), c[2], d);
                if (!isNaN(n)) {
                    var l = d / 7;
                    var m = false;
                    var h = this.possibleCenters.length;
                    for (var g = 0; g < h; g++) {
                        var a = this.possibleCenters[g];
                        if (a.aboutEquals(l, b, n)) {
                            a.incrementCount();
                            m = true;
                            break
                        }
                    }
                    if (!m) {
                        var k = new FinderPattern(n, b, l);
                        this.possibleCenters.push(k);
                        if (this.resultPointCallback != null) {
                            this.resultPointCallback.foundPossibleResultPoint(k)
                        }
                    }
                    return true
                }
            }
            return false
        };
        this.selectBestPatterns = function () {
            var a = this.possibleCenters.length;
            if (a < 3) {
                throw"Couldn't find enough finder patterns"
            }
            if (a > 3) {
                var b = 0;
                for (var c = 0; c < a; c++) {
                    b += this.possibleCenters[c].EstimatedModuleSize
                }
                var d = b / a;
                for (var c = 0; c < this.possibleCenters.length && this.possibleCenters.length > 3; c++) {
                    var e = this.possibleCenters[c];
                    if (Math.abs(e.EstimatedModuleSize - d) > 0.2 * d) {
                        this.possibleCenters.remove(c);
                        c--
                    }
                }
            }
            if (this.possibleCenters.Count > 3) {
            }
            return new Array(this.possibleCenters[0], this.possibleCenters[1], this.possibleCenters[2])
        };
        this.findRowSkip = function () {
            var b = this.possibleCenters.length;
            if (b <= 1) {
                return 0
            }
            var c = null;
            for (var d = 0; d < b; d++) {
                var a = this.possibleCenters[d];
                if (a.Count >= CENTER_QUORUM) {
                    if (c == null) {
                        c = a
                    } else {
                        this.hasSkipped = true;
                        return Math.floor((Math.abs(c.X - a.X) - Math.abs(c.Y - a.Y)) / 2)
                    }
                }
            }
            return 0
        };
        this.haveMultiplyConfirmedCenters = function () {
            var g = 0;
            var c = 0;
            var a = this.possibleCenters.length;
            var d;
            for (d = 0; d < a; d++) {
                var f = this.possibleCenters[d];
                if (f.Count >= CENTER_QUORUM) {
                    g++;
                    c += f.EstimatedModuleSize
                }
            }
            if (g < 3) {
                return false
            }
            var e = c / a;
            var b = 0;
            for (d = 0; d < a; d++) {
                f = this.possibleCenters[d];
                b += Math.abs(f.EstimatedModuleSize - e)
            }
            return b <= 0.05 * c
        };
        this.findFinderPattern = function (e) {
            var o = false;
            this.image = e;
            var n = qrcode.height;
            var k = qrcode.width;
            var a = Math.floor((3 * n) / (4 * MAX_MODULES));
            if (a < MIN_SKIP || o) {
                a = MIN_SKIP
            }
            var g = false;
            var d = new Array(5);
            for (var h = a - 1; h < n && !g; h += a) {
                d[0] = 0;
                d[1] = 0;
                d[2] = 0;
                d[3] = 0;
                d[4] = 0;
                var b = 0;
                for (var f = 0; f < k; f++) {
                    if (e[f + h * qrcode.width]) {
                        if ((b & 1) == 1) {
                            b++
                        }
                        d[b]++
                    } else {
                        if ((b & 1) == 0) {
                            if (b == 4) {
                                if (this.foundPatternCross(d)) {
                                    var c = this.handlePossibleCenter(d, h, f);
                                    if (c) {
                                        a = 2;
                                        if (this.hasSkipped) {
                                            g = this.haveMultiplyConfirmedCenters()
                                        } else {
                                            var m = this.findRowSkip();
                                            if (m > d[2]) {
                                                h += m - d[2] - a;
                                                f = k - 1
                                            }
                                        }
                                    } else {
                                        do {
                                            f++
                                        } while (f < k && !e[f + h * qrcode.width]);
                                        f--
                                    }
                                    b = 0;
                                    d[0] = 0;
                                    d[1] = 0;
                                    d[2] = 0;
                                    d[3] = 0;
                                    d[4] = 0
                                } else {
                                    d[0] = d[2];
                                    d[1] = d[3];
                                    d[2] = d[4];
                                    d[3] = 1;
                                    d[4] = 0;
                                    b = 3
                                }
                            } else {
                                d[++b]++
                            }
                        } else {
                            d[b]++
                        }
                    }
                }
                if (this.foundPatternCross(d)) {
                    var c = this.handlePossibleCenter(d, h, k);
                    if (c) {
                        a = d[0];
                        if (this.hasSkipped) {
                            g = haveMultiplyConfirmedCenters()
                        }
                    }
                }
            }
            var l = this.selectBestPatterns();
            qrcode.orderBestPatterns(l);
            return new FinderPatternInfo(l)
        }
    }

    function AlignmentPattern(c, a, b) {
        this.x = c;
        this.y = a;
        this.count = 1;
        this.estimatedModuleSize = b;
        this.__defineGetter__("EstimatedModuleSize", function () {
            return this.estimatedModuleSize
        });
        this.__defineGetter__("Count", function () {
            return this.count
        });
        this.__defineGetter__("X", function () {
            return Math.floor(this.x)
        });
        this.__defineGetter__("Y", function () {
            return Math.floor(this.y)
        });
        this.incrementCount = function () {
            this.count++
        };
        this.aboutEquals = function (f, e, d) {
            if (Math.abs(e - this.y) <= f && Math.abs(d - this.x) <= f) {
                var g = Math.abs(f - this.estimatedModuleSize);
                return g <= 1 || g / this.estimatedModuleSize <= 1
            }
            return false
        }
    }

    function AlignmentPatternFinder(g, c, b, f, a, e, d) {
        this.image = g;
        this.possibleCenters = new Array();
        this.startX = c;
        this.startY = b;
        this.width = f;
        this.height = a;
        this.moduleSize = e;
        this.crossCheckStateCount = new Array(0, 0, 0);
        this.resultPointCallback = d;
        this.centerFromEnd = function (i, h) {
            return(h - i[2]) - i[1] / 2
        };
        this.foundPatternCross = function (l) {
            var k = this.moduleSize;
            var h = k / 2;
            for (var j = 0; j < 3; j++) {
                if (Math.abs(k - l[j]) >= h) {
                    return false
                }
            }
            return true
        };
        this.crossCheckVertical = function (h, r, l, o) {
            var k = this.image;
            var q = qrcode.height;
            var j = this.crossCheckStateCount;
            j[0] = 0;
            j[1] = 0;
            j[2] = 0;
            var n = h;
            while (n >= 0 && k[r + n * qrcode.width] && j[1] <= l) {
                j[1]++;
                n--
            }
            if (n < 0 || j[1] > l) {
                return NaN
            }
            while (n >= 0 && !k[r + n * qrcode.width] && j[0] <= l) {
                j[0]++;
                n--
            }
            if (j[0] > l) {
                return NaN
            }
            n = h + 1;
            while (n < q && k[r + n * qrcode.width] && j[1] <= l) {
                j[1]++;
                n++
            }
            if (n == q || j[1] > l) {
                return NaN
            }
            while (n < q && !k[r + n * qrcode.width] && j[2] <= l) {
                j[2]++;
                n++
            }
            if (j[2] > l) {
                return NaN
            }
            var m = j[0] + j[1] + j[2];
            if (5 * Math.abs(m - o) >= 2 * o) {
                return NaN
            }
            return this.foundPatternCross(j) ? this.centerFromEnd(j, n) : NaN
        };
        this.handlePossibleCenter = function (l, o, n) {
            var m = l[0] + l[1] + l[2];
            var u = this.centerFromEnd(l, n);
            var k = this.crossCheckVertical(o, Math.floor(u), 2 * l[1], m);
            if (!isNaN(k)) {
                var t = (l[0] + l[1] + l[2]) / 3;
                var r = this.possibleCenters.length;
                for (var q = 0; q < r; q++) {
                    var h = this.possibleCenters[q];
                    if (h.aboutEquals(t, k, u)) {
                        return new AlignmentPattern(u, k, t)
                    }
                }
                var s = new AlignmentPattern(u, k, t);
                this.possibleCenters.push(s);
                if (this.resultPointCallback != null) {
                    this.resultPointCallback.foundPossibleResultPoint(s)
                }
            }
            return null
        };
        this.find = function () {
            var q = this.startX;
            var t = this.height;
            var r = q + f;
            var s = b + (t >> 1);
            var m = new Array(0, 0, 0);
            for (var k = 0; k < t; k++) {
                var o = s + ((k & 1) == 0 ? ((k + 1) >> 1) : -((k + 1) >> 1));
                m[0] = 0;
                m[1] = 0;
                m[2] = 0;
                var n = q;
                while (n < r && !g[n + qrcode.width * o]) {
                    n++
                }
                var h = 0;
                while (n < r) {
                    if (g[n + o * qrcode.width]) {
                        if (h == 1) {
                            m[h]++
                        } else {
                            if (h == 2) {
                                if (this.foundPatternCross(m)) {
                                    var l = this.handlePossibleCenter(m, o, n);
                                    if (l != null) {
                                        return l
                                    }
                                }
                                m[0] = m[2];
                                m[1] = 1;
                                m[2] = 0;
                                h = 1
                            } else {
                                m[++h]++
                            }
                        }
                    } else {
                        if (h == 1) {
                            h++
                        }
                        m[h]++
                    }
                    n++
                }
                if (this.foundPatternCross(m)) {
                    var l = this.handlePossibleCenter(m, o, r);
                    if (l != null) {
                        return l
                    }
                }
            }
            if (!(this.possibleCenters.length == 0)) {
                return this.possibleCenters[0]
            }
            throw"Couldn't find enough alignment patterns"
        }
    }

    function QRCodeDataBlockReader(c, a, b) {
        this.blockPointer = 0;
        this.bitPointer = 7;
        this.dataLength = 0;
        this.blocks = c;
        this.numErrorCorrectionCode = b;
        if (a <= 9) {
            this.dataLengthMode = 0
        } else {
            if (a >= 10 && a <= 26) {
                this.dataLengthMode = 1
            } else {
                if (a >= 27 && a <= 40) {
                    this.dataLengthMode = 2
                }
            }
        }
        this.getNextBits = function (f) {
            var k = 0,
                e,
                j;
            if (f < this.bitPointer + 1) {
                var m = 0;
                for (e = 0; e < f; e++) {
                    m += (1 << e)
                }
                m <<= (this.bitPointer - f + 1);
                k = (this.blocks[this.blockPointer] & m) >> (this.bitPointer - f + 1);
                this.bitPointer -= f;
                return k
            } else {
                if (f < this.bitPointer + 1 + 8) {
                    j = 0;
                    for (e = 0; e < this.bitPointer + 1; e++) {
                        j += (1 << e)
                    }
                    k = (this.blocks[this.blockPointer] & j) << (f - (this.bitPointer + 1));
                    this.blockPointer++;
                    k += ((this.blocks[this.blockPointer]) >> (8 - (f - (this.bitPointer + 1))));
                    this.bitPointer = this.bitPointer - f % 8;
                    if (this.bitPointer < 0) {
                        this.bitPointer = 8 + this.bitPointer
                    }
                    return k
                } else {
                    if (f < this.bitPointer + 1 + 16) {
                        j = 0;
                        var h = 0;
                        for (e = 0; e < this.bitPointer + 1; e++) {
                            j += (1 << e)
                        }
                        var g = (this.blocks[this.blockPointer] & j) << (f - (this.bitPointer + 1));
                        this.blockPointer++;
                        var d = this.blocks[this.blockPointer] << (f - (this.bitPointer + 1 + 8));
                        this.blockPointer++;
                        for (e = 0; e < f - (this.bitPointer + 1 + 8); e++) {
                            h += (1 << e)
                        }
                        h <<= 8 - (f - (this.bitPointer + 1 + 8));
                        var l = (this.blocks[this.blockPointer] & h) >> (8 - (f - (this.bitPointer + 1 + 8)));
                        k = g + d + l;
                        this.bitPointer = this.bitPointer - (f - 8) % 8;
                        if (this.bitPointer < 0) {
                            this.bitPointer = 8 + this.bitPointer
                        }
                        return k
                    } else {
                        return 0
                    }
                }
            }
        };
        this.NextMode = function () {
            if ((this.blockPointer > this.blocks.length - this.numErrorCorrectionCode - 2)) {
                return 0
            } else {
                return this.getNextBits(4)
            }
        };
        this.getDataLength = function (d) {
            var e = 0;
            while (true) {
                if ((d >> e) == 1) {
                    break
                }
                e++
            }
            return this.getNextBits(qrcode.sizeOfDataLengthInfo[this.dataLengthMode][e])
        };
        this.getRomanAndFigureString = function (h) {
            var f = h;
            var g = 0;
            var j = "";
            var d = new Array("0", "1", "2", "3", "4", "5", "6", "7", "8", "9", "A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R", "S", "T", "U", "V", "W", "X", "Y", "Z", " ", "$", "%", "*", "+", "-", ".", "/", ":");
            do {
                if (f > 1) {
                    g = this.getNextBits(11);
                    var i = Math.floor(g / 45);
                    var e = g % 45;
                    j += d[i];
                    j += d[e];
                    f -= 2
                } else {
                    if (f == 1) {
                        g = this.getNextBits(6);
                        j += d[g];
                        f -= 1
                    }
                }
            } while (f > 0);
            return j
        };
        this.getFigureString = function (f) {
            var d = f;
            var e = 0;
            var g = "";
            do {
                if (d >= 3) {
                    e = this.getNextBits(10);
                    if (e < 100) {
                        g += "0"
                    }
                    if (e < 10) {
                        g += "0"
                    }
                    d -= 3
                } else {
                    if (d == 2) {
                        e = this.getNextBits(7);
                        if (e < 10) {
                            g += "0"
                        }
                        d -= 2
                    } else {
                        if (d == 1) {
                            e = this.getNextBits(4);
                            d -= 1
                        }
                    }
                }
                g += e
            } while (d > 0);
            return g
        };
        this.get8bitByteArray = function (g) {
            var e = g;
            var f = 0;
            var d = new Array();
            do {
                f = this.getNextBits(8);
                d.push(f);
                e--
            } while (e > 0);
            return d
        };
        this.getKanjiString = function (j) {
            var g = j;
            var i = 0;
            var h = "";
            do {
                i = getNextBits(13);
                var e = i % 192;
                var f = i / 192;
                var k = (f << 8) + e;
                var d = 0;
                if (k + 33088 <= 40956) {
                    d = k + 33088
                } else {
                    d = k + 49472
                }
                h += String.fromCharCode(d);
                g--
            } while (g > 0);
            return h
        };
        this.__defineGetter__("DataByte", function () {
            var g = new Array();
            var e = 1;
            var f = 2;
            var d = 4;
            var n = 8;
            do {
                var k = this.NextMode();
                if (k == 0) {
                    if (g.length > 0) {
                        break
                    } else {
                        throw"Empty data block"
                    }
                }
                if (k != e && k != f && k != d && k != n) {
                    throw"Invalid mode: " + k + " in (block:" + this.blockPointer + " bit:" + this.bitPointer + ")"
                }
                var dataLength = this.getDataLength(k);
                if (dataLength < 1) {
                    throw"Invalid data length: " + dataLength
                }
                switch (k) {
                    case e:
                        var l = this.getFigureString(dataLength);
                        var i = new Array(l.length);
                        for (var h = 0; h < l.length; h++) {
                            i[h] = l.charCodeAt(h)
                        }
                        g.push(i);
                        break;
                    case f:
                        var l = this.getRomanAndFigureString(dataLength);
                        var i = new Array(l.length);
                        for (var h = 0; h < l.length; h++) {
                            i[h] = l.charCodeAt(h)
                        }
                        g.push(i);
                        break;
                    case d:
                        var m = this.get8bitByteArray(dataLength);
                        g.push(m);
                        break;
                    case n:
                        var l = this.getKanjiString(dataLength);
                        g.push(l);
                        break
                }
            } while (true);
            return g
        })
    }

    root.qrcode = qrcode;

})(this);