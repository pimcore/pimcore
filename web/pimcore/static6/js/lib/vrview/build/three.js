(function (global, factory) {
	typeof exports === 'object' && typeof module !== 'undefined' ? factory(exports) :
	typeof define === 'function' && define.amd ? define(['exports'], factory) :
	(factory((global.THREE = global.THREE || {})));
}(this, (function (exports) { 'use strict';

// Polyfills

if ( Number.EPSILON === undefined ) {

	Number.EPSILON = Math.pow( 2, - 52 );

}

//

if ( Math.sign === undefined ) {

	// https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Math/sign

	Math.sign = function ( x ) {

		return ( x < 0 ) ? - 1 : ( x > 0 ) ? 1 : + x;

	};

}

if ( Function.prototype.name === undefined ) {

	// Missing in IE9-11.
	// https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Function/name

	Object.defineProperty( Function.prototype, 'name', {

		get: function () {

			return this.toString().match( /^\s*function\s*([^\(\s]*)/ )[ 1 ];

		}

	} );

}

if ( Object.assign === undefined ) {

	// Missing in IE.
	// https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Object/assign

	( function () {

		Object.assign = function ( target ) {

			'use strict';

			if ( target === undefined || target === null ) {

				throw new TypeError( 'Cannot convert undefined or null to object' );

			}

			var output = Object( target );

			for ( var index = 1; index < arguments.length; index ++ ) {

				var source = arguments[ index ];

				if ( source !== undefined && source !== null ) {

					for ( var nextKey in source ) {

						if ( Object.prototype.hasOwnProperty.call( source, nextKey ) ) {

							output[ nextKey ] = source[ nextKey ];

						}

					}

				}

			}

			return output;

		};

	} )();

}

var REVISION = '84';
var MOUSE = { LEFT: 0, MIDDLE: 1, RIGHT: 2 };
var CullFaceNone = 0;
var CullFaceBack = 1;
var CullFaceFront = 2;
var CullFaceFrontBack = 3;
var FrontFaceDirectionCW = 0;
var FrontFaceDirectionCCW = 1;
var BasicShadowMap = 0;
var PCFShadowMap = 1;
var PCFSoftShadowMap = 2;
var FrontSide = 0;
var BackSide = 1;
var DoubleSide = 2;
var FlatShading = 1;
var SmoothShading = 2;
var NoColors = 0;
var FaceColors = 1;
var VertexColors = 2;
var NoBlending = 0;
var NormalBlending = 1;
var AdditiveBlending = 2;
var SubtractiveBlending = 3;
var MultiplyBlending = 4;
var CustomBlending = 5;
var AddEquation = 100;
var SubtractEquation = 101;
var ReverseSubtractEquation = 102;
var MinEquation = 103;
var MaxEquation = 104;
var ZeroFactor = 200;
var OneFactor = 201;
var SrcColorFactor = 202;
var OneMinusSrcColorFactor = 203;
var SrcAlphaFactor = 204;
var OneMinusSrcAlphaFactor = 205;
var DstAlphaFactor = 206;
var OneMinusDstAlphaFactor = 207;
var DstColorFactor = 208;
var OneMinusDstColorFactor = 209;
var SrcAlphaSaturateFactor = 210;
var NeverDepth = 0;
var AlwaysDepth = 1;
var LessDepth = 2;
var LessEqualDepth = 3;
var EqualDepth = 4;
var GreaterEqualDepth = 5;
var GreaterDepth = 6;
var NotEqualDepth = 7;
var MultiplyOperation = 0;
var MixOperation = 1;
var AddOperation = 2;
var NoToneMapping = 0;
var LinearToneMapping = 1;
var ReinhardToneMapping = 2;
var Uncharted2ToneMapping = 3;
var CineonToneMapping = 4;
var UVMapping = 300;
var CubeReflectionMapping = 301;
var CubeRefractionMapping = 302;
var EquirectangularReflectionMapping = 303;
var EquirectangularRefractionMapping = 304;
var SphericalReflectionMapping = 305;
var CubeUVReflectionMapping = 306;
var CubeUVRefractionMapping = 307;
var RepeatWrapping = 1000;
var ClampToEdgeWrapping = 1001;
var MirroredRepeatWrapping = 1002;
var NearestFilter = 1003;
var NearestMipMapNearestFilter = 1004;
var NearestMipMapLinearFilter = 1005;
var LinearFilter = 1006;
var LinearMipMapNearestFilter = 1007;
var LinearMipMapLinearFilter = 1008;
var UnsignedByteType = 1009;
var ByteType = 1010;
var ShortType = 1011;
var UnsignedShortType = 1012;
var IntType = 1013;
var UnsignedIntType = 1014;
var FloatType = 1015;
var HalfFloatType = 1016;
var UnsignedShort4444Type = 1017;
var UnsignedShort5551Type = 1018;
var UnsignedShort565Type = 1019;
var UnsignedInt248Type = 1020;
var AlphaFormat = 1021;
var RGBFormat = 1022;
var RGBAFormat = 1023;
var LuminanceFormat = 1024;
var LuminanceAlphaFormat = 1025;
var RGBEFormat = RGBAFormat;
var DepthFormat = 1026;
var DepthStencilFormat = 1027;
var RGB_S3TC_DXT1_Format = 2001;
var RGBA_S3TC_DXT1_Format = 2002;
var RGBA_S3TC_DXT3_Format = 2003;
var RGBA_S3TC_DXT5_Format = 2004;
var RGB_PVRTC_4BPPV1_Format = 2100;
var RGB_PVRTC_2BPPV1_Format = 2101;
var RGBA_PVRTC_4BPPV1_Format = 2102;
var RGBA_PVRTC_2BPPV1_Format = 2103;
var RGB_ETC1_Format = 2151;
var LoopOnce = 2200;
var LoopRepeat = 2201;
var LoopPingPong = 2202;
var InterpolateDiscrete = 2300;
var InterpolateLinear = 2301;
var InterpolateSmooth = 2302;
var ZeroCurvatureEnding = 2400;
var ZeroSlopeEnding = 2401;
var WrapAroundEnding = 2402;
var TrianglesDrawMode = 0;
var TriangleStripDrawMode = 1;
var TriangleFanDrawMode = 2;
var LinearEncoding = 3000;
var sRGBEncoding = 3001;
var GammaEncoding = 3007;
var RGBEEncoding = 3002;
var LogLuvEncoding = 3003;
var RGBM7Encoding = 3004;
var RGBM16Encoding = 3005;
var RGBDEncoding = 3006;
var BasicDepthPacking = 3200;
var RGBADepthPacking = 3201;

/**
 * @author alteredq / http://alteredqualia.com/
 * @author mrdoob / http://mrdoob.com/
 */

var _Math = {

	DEG2RAD: Math.PI / 180,
	RAD2DEG: 180 / Math.PI,

	generateUUID: function () {

		// http://www.broofa.com/Tools/Math.uuid.htm

		var chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz'.split( '' );
		var uuid = new Array( 36 );
		var rnd = 0, r;

		return function generateUUID() {

			for ( var i = 0; i < 36; i ++ ) {

				if ( i === 8 || i === 13 || i === 18 || i === 23 ) {

					uuid[ i ] = '-';

				} else if ( i === 14 ) {

					uuid[ i ] = '4';

				} else {

					if ( rnd <= 0x02 ) rnd = 0x2000000 + ( Math.random() * 0x1000000 ) | 0;
					r = rnd & 0xf;
					rnd = rnd >> 4;
					uuid[ i ] = chars[ ( i === 19 ) ? ( r & 0x3 ) | 0x8 : r ];

				}

			}

			return uuid.join( '' );

		};

	}(),

	clamp: function ( value, min, max ) {

		return Math.max( min, Math.min( max, value ) );

	},

	// compute euclidian modulo of m % n
	// https://en.wikipedia.org/wiki/Modulo_operation

	euclideanModulo: function ( n, m ) {

		return ( ( n % m ) + m ) % m;

	},

	// Linear mapping from range <a1, a2> to range <b1, b2>

	mapLinear: function ( x, a1, a2, b1, b2 ) {

		return b1 + ( x - a1 ) * ( b2 - b1 ) / ( a2 - a1 );

	},

	// https://en.wikipedia.org/wiki/Linear_interpolation

	lerp: function ( x, y, t ) {

		return ( 1 - t ) * x + t * y;

	},

	// http://en.wikipedia.org/wiki/Smoothstep

	smoothstep: function ( x, min, max ) {

		if ( x <= min ) return 0;
		if ( x >= max ) return 1;

		x = ( x - min ) / ( max - min );

		return x * x * ( 3 - 2 * x );

	},

	smootherstep: function ( x, min, max ) {

		if ( x <= min ) return 0;
		if ( x >= max ) return 1;

		x = ( x - min ) / ( max - min );

		return x * x * x * ( x * ( x * 6 - 15 ) + 10 );

	},

	// Random integer from <low, high> interval

	randInt: function ( low, high ) {

		return low + Math.floor( Math.random() * ( high - low + 1 ) );

	},

	// Random float from <low, high> interval

	randFloat: function ( low, high ) {

		return low + Math.random() * ( high - low );

	},

	// Random float from <-range/2, range/2> interval

	randFloatSpread: function ( range ) {

		return range * ( 0.5 - Math.random() );

	},

	degToRad: function ( degrees ) {

		return degrees * _Math.DEG2RAD;

	},

	radToDeg: function ( radians ) {

		return radians * _Math.RAD2DEG;

	},

	isPowerOfTwo: function ( value ) {

		return ( value & ( value - 1 ) ) === 0 && value !== 0;

	},

	nearestPowerOfTwo: function ( value ) {

		return Math.pow( 2, Math.round( Math.log( value ) / Math.LN2 ) );

	},

	nextPowerOfTwo: function ( value ) {

		value --;
		value |= value >> 1;
		value |= value >> 2;
		value |= value >> 4;
		value |= value >> 8;
		value |= value >> 16;
		value ++;

		return value;

	}

};

/**
 * @author mikael emtinger / http://gomo.se/
 * @author alteredq / http://alteredqualia.com/
 * @author WestLangley / http://github.com/WestLangley
 * @author bhouston / http://clara.io
 */

function Quaternion( x, y, z, w ) {

	this._x = x || 0;
	this._y = y || 0;
	this._z = z || 0;
	this._w = ( w !== undefined ) ? w : 1;

}

Quaternion.prototype = {

	constructor: Quaternion,

	get x () {

		return this._x;

	},

	set x ( value ) {

		this._x = value;
		this.onChangeCallback();

	},

	get y () {

		return this._y;

	},

	set y ( value ) {

		this._y = value;
		this.onChangeCallback();

	},

	get z () {

		return this._z;

	},

	set z ( value ) {

		this._z = value;
		this.onChangeCallback();

	},

	get w () {

		return this._w;

	},

	set w ( value ) {

		this._w = value;
		this.onChangeCallback();

	},

	set: function ( x, y, z, w ) {

		this._x = x;
		this._y = y;
		this._z = z;
		this._w = w;

		this.onChangeCallback();

		return this;

	},

	clone: function () {

		return new this.constructor( this._x, this._y, this._z, this._w );

	},

	copy: function ( quaternion ) {

		this._x = quaternion.x;
		this._y = quaternion.y;
		this._z = quaternion.z;
		this._w = quaternion.w;

		this.onChangeCallback();

		return this;

	},

	setFromEuler: function ( euler, update ) {

		if ( (euler && euler.isEuler) === false ) {

			throw new Error( 'THREE.Quaternion: .setFromEuler() now expects an Euler rotation rather than a Vector3 and order.' );

		}

		// http://www.mathworks.com/matlabcentral/fileexchange/
		// 	20696-function-to-convert-between-dcm-euler-angles-quaternions-and-euler-vectors/
		//	content/SpinCalc.m

		var c1 = Math.cos( euler._x / 2 );
		var c2 = Math.cos( euler._y / 2 );
		var c3 = Math.cos( euler._z / 2 );
		var s1 = Math.sin( euler._x / 2 );
		var s2 = Math.sin( euler._y / 2 );
		var s3 = Math.sin( euler._z / 2 );

		var order = euler.order;

		if ( order === 'XYZ' ) {

			this._x = s1 * c2 * c3 + c1 * s2 * s3;
			this._y = c1 * s2 * c3 - s1 * c2 * s3;
			this._z = c1 * c2 * s3 + s1 * s2 * c3;
			this._w = c1 * c2 * c3 - s1 * s2 * s3;

		} else if ( order === 'YXZ' ) {

			this._x = s1 * c2 * c3 + c1 * s2 * s3;
			this._y = c1 * s2 * c3 - s1 * c2 * s3;
			this._z = c1 * c2 * s3 - s1 * s2 * c3;
			this._w = c1 * c2 * c3 + s1 * s2 * s3;

		} else if ( order === 'ZXY' ) {

			this._x = s1 * c2 * c3 - c1 * s2 * s3;
			this._y = c1 * s2 * c3 + s1 * c2 * s3;
			this._z = c1 * c2 * s3 + s1 * s2 * c3;
			this._w = c1 * c2 * c3 - s1 * s2 * s3;

		} else if ( order === 'ZYX' ) {

			this._x = s1 * c2 * c3 - c1 * s2 * s3;
			this._y = c1 * s2 * c3 + s1 * c2 * s3;
			this._z = c1 * c2 * s3 - s1 * s2 * c3;
			this._w = c1 * c2 * c3 + s1 * s2 * s3;

		} else if ( order === 'YZX' ) {

			this._x = s1 * c2 * c3 + c1 * s2 * s3;
			this._y = c1 * s2 * c3 + s1 * c2 * s3;
			this._z = c1 * c2 * s3 - s1 * s2 * c3;
			this._w = c1 * c2 * c3 - s1 * s2 * s3;

		} else if ( order === 'XZY' ) {

			this._x = s1 * c2 * c3 - c1 * s2 * s3;
			this._y = c1 * s2 * c3 - s1 * c2 * s3;
			this._z = c1 * c2 * s3 + s1 * s2 * c3;
			this._w = c1 * c2 * c3 + s1 * s2 * s3;

		}

		if ( update !== false ) this.onChangeCallback();

		return this;

	},

	setFromAxisAngle: function ( axis, angle ) {

		// http://www.euclideanspace.com/maths/geometry/rotations/conversions/angleToQuaternion/index.htm

		// assumes axis is normalized

		var halfAngle = angle / 2, s = Math.sin( halfAngle );

		this._x = axis.x * s;
		this._y = axis.y * s;
		this._z = axis.z * s;
		this._w = Math.cos( halfAngle );

		this.onChangeCallback();

		return this;

	},

	setFromRotationMatrix: function ( m ) {

		// http://www.euclideanspace.com/maths/geometry/rotations/conversions/matrixToQuaternion/index.htm

		// assumes the upper 3x3 of m is a pure rotation matrix (i.e, unscaled)

		var te = m.elements,

			m11 = te[ 0 ], m12 = te[ 4 ], m13 = te[ 8 ],
			m21 = te[ 1 ], m22 = te[ 5 ], m23 = te[ 9 ],
			m31 = te[ 2 ], m32 = te[ 6 ], m33 = te[ 10 ],

			trace = m11 + m22 + m33,
			s;

		if ( trace > 0 ) {

			s = 0.5 / Math.sqrt( trace + 1.0 );

			this._w = 0.25 / s;
			this._x = ( m32 - m23 ) * s;
			this._y = ( m13 - m31 ) * s;
			this._z = ( m21 - m12 ) * s;

		} else if ( m11 > m22 && m11 > m33 ) {

			s = 2.0 * Math.sqrt( 1.0 + m11 - m22 - m33 );

			this._w = ( m32 - m23 ) / s;
			this._x = 0.25 * s;
			this._y = ( m12 + m21 ) / s;
			this._z = ( m13 + m31 ) / s;

		} else if ( m22 > m33 ) {

			s = 2.0 * Math.sqrt( 1.0 + m22 - m11 - m33 );

			this._w = ( m13 - m31 ) / s;
			this._x = ( m12 + m21 ) / s;
			this._y = 0.25 * s;
			this._z = ( m23 + m32 ) / s;

		} else {

			s = 2.0 * Math.sqrt( 1.0 + m33 - m11 - m22 );

			this._w = ( m21 - m12 ) / s;
			this._x = ( m13 + m31 ) / s;
			this._y = ( m23 + m32 ) / s;
			this._z = 0.25 * s;

		}

		this.onChangeCallback();

		return this;

	},

	setFromUnitVectors: function () {

		// http://lolengine.net/blog/2014/02/24/quaternion-from-two-vectors-final

		// assumes direction vectors vFrom and vTo are normalized

		var v1, r;

		var EPS = 0.000001;

		return function setFromUnitVectors( vFrom, vTo ) {

			if ( v1 === undefined ) v1 = new Vector3();

			r = vFrom.dot( vTo ) + 1;

			if ( r < EPS ) {

				r = 0;

				if ( Math.abs( vFrom.x ) > Math.abs( vFrom.z ) ) {

					v1.set( - vFrom.y, vFrom.x, 0 );

				} else {

					v1.set( 0, - vFrom.z, vFrom.y );

				}

			} else {

				v1.crossVectors( vFrom, vTo );

			}

			this._x = v1.x;
			this._y = v1.y;
			this._z = v1.z;
			this._w = r;

			return this.normalize();

		};

	}(),

	inverse: function () {

		return this.conjugate().normalize();

	},

	conjugate: function () {

		this._x *= - 1;
		this._y *= - 1;
		this._z *= - 1;

		this.onChangeCallback();

		return this;

	},

	dot: function ( v ) {

		return this._x * v._x + this._y * v._y + this._z * v._z + this._w * v._w;

	},

	lengthSq: function () {

		return this._x * this._x + this._y * this._y + this._z * this._z + this._w * this._w;

	},

	length: function () {

		return Math.sqrt( this._x * this._x + this._y * this._y + this._z * this._z + this._w * this._w );

	},

	normalize: function () {

		var l = this.length();

		if ( l === 0 ) {

			this._x = 0;
			this._y = 0;
			this._z = 0;
			this._w = 1;

		} else {

			l = 1 / l;

			this._x = this._x * l;
			this._y = this._y * l;
			this._z = this._z * l;
			this._w = this._w * l;

		}

		this.onChangeCallback();

		return this;

	},

	multiply: function ( q, p ) {

		if ( p !== undefined ) {

			console.warn( 'THREE.Quaternion: .multiply() now only accepts one argument. Use .multiplyQuaternions( a, b ) instead.' );
			return this.multiplyQuaternions( q, p );

		}

		return this.multiplyQuaternions( this, q );

	},

	premultiply: function ( q ) {

		return this.multiplyQuaternions( q, this );

	},

	multiplyQuaternions: function ( a, b ) {

		// from http://www.euclideanspace.com/maths/algebra/realNormedAlgebra/quaternions/code/index.htm

		var qax = a._x, qay = a._y, qaz = a._z, qaw = a._w;
		var qbx = b._x, qby = b._y, qbz = b._z, qbw = b._w;

		this._x = qax * qbw + qaw * qbx + qay * qbz - qaz * qby;
		this._y = qay * qbw + qaw * qby + qaz * qbx - qax * qbz;
		this._z = qaz * qbw + qaw * qbz + qax * qby - qay * qbx;
		this._w = qaw * qbw - qax * qbx - qay * qby - qaz * qbz;

		this.onChangeCallback();

		return this;

	},

	slerp: function ( qb, t ) {

		if ( t === 0 ) return this;
		if ( t === 1 ) return this.copy( qb );

		var x = this._x, y = this._y, z = this._z, w = this._w;

		// http://www.euclideanspace.com/maths/algebra/realNormedAlgebra/quaternions/slerp/

		var cosHalfTheta = w * qb._w + x * qb._x + y * qb._y + z * qb._z;

		if ( cosHalfTheta < 0 ) {

			this._w = - qb._w;
			this._x = - qb._x;
			this._y = - qb._y;
			this._z = - qb._z;

			cosHalfTheta = - cosHalfTheta;

		} else {

			this.copy( qb );

		}

		if ( cosHalfTheta >= 1.0 ) {

			this._w = w;
			this._x = x;
			this._y = y;
			this._z = z;

			return this;

		}

		var sinHalfTheta = Math.sqrt( 1.0 - cosHalfTheta * cosHalfTheta );

		if ( Math.abs( sinHalfTheta ) < 0.001 ) {

			this._w = 0.5 * ( w + this._w );
			this._x = 0.5 * ( x + this._x );
			this._y = 0.5 * ( y + this._y );
			this._z = 0.5 * ( z + this._z );

			return this;

		}

		var halfTheta = Math.atan2( sinHalfTheta, cosHalfTheta );
		var ratioA = Math.sin( ( 1 - t ) * halfTheta ) / sinHalfTheta,
		ratioB = Math.sin( t * halfTheta ) / sinHalfTheta;

		this._w = ( w * ratioA + this._w * ratioB );
		this._x = ( x * ratioA + this._x * ratioB );
		this._y = ( y * ratioA + this._y * ratioB );
		this._z = ( z * ratioA + this._z * ratioB );

		this.onChangeCallback();

		return this;

	},

	equals: function ( quaternion ) {

		return ( quaternion._x === this._x ) && ( quaternion._y === this._y ) && ( quaternion._z === this._z ) && ( quaternion._w === this._w );

	},

	fromArray: function ( array, offset ) {

		if ( offset === undefined ) offset = 0;

		this._x = array[ offset ];
		this._y = array[ offset + 1 ];
		this._z = array[ offset + 2 ];
		this._w = array[ offset + 3 ];

		this.onChangeCallback();

		return this;

	},

	toArray: function ( array, offset ) {

		if ( array === undefined ) array = [];
		if ( offset === undefined ) offset = 0;

		array[ offset ] = this._x;
		array[ offset + 1 ] = this._y;
		array[ offset + 2 ] = this._z;
		array[ offset + 3 ] = this._w;

		return array;

	},

	onChange: function ( callback ) {

		this.onChangeCallback = callback;

		return this;

	},

	onChangeCallback: function () {}

};

Object.assign( Quaternion, {

	slerp: function( qa, qb, qm, t ) {

		return qm.copy( qa ).slerp( qb, t );

	},

	slerpFlat: function(
			dst, dstOffset, src0, srcOffset0, src1, srcOffset1, t ) {

		// fuzz-free, array-based Quaternion SLERP operation

		var x0 = src0[ srcOffset0 + 0 ],
			y0 = src0[ srcOffset0 + 1 ],
			z0 = src0[ srcOffset0 + 2 ],
			w0 = src0[ srcOffset0 + 3 ],

			x1 = src1[ srcOffset1 + 0 ],
			y1 = src1[ srcOffset1 + 1 ],
			z1 = src1[ srcOffset1 + 2 ],
			w1 = src1[ srcOffset1 + 3 ];

		if ( w0 !== w1 || x0 !== x1 || y0 !== y1 || z0 !== z1 ) {

			var s = 1 - t,

				cos = x0 * x1 + y0 * y1 + z0 * z1 + w0 * w1,

				dir = ( cos >= 0 ? 1 : - 1 ),
				sqrSin = 1 - cos * cos;

			// Skip the Slerp for tiny steps to avoid numeric problems:
			if ( sqrSin > Number.EPSILON ) {

				var sin = Math.sqrt( sqrSin ),
					len = Math.atan2( sin, cos * dir );

				s = Math.sin( s * len ) / sin;
				t = Math.sin( t * len ) / sin;

			}

			var tDir = t * dir;

			x0 = x0 * s + x1 * tDir;
			y0 = y0 * s + y1 * tDir;
			z0 = z0 * s + z1 * tDir;
			w0 = w0 * s + w1 * tDir;

			// Normalize in case we just did a lerp:
			if ( s === 1 - t ) {

				var f = 1 / Math.sqrt( x0 * x0 + y0 * y0 + z0 * z0 + w0 * w0 );

				x0 *= f;
				y0 *= f;
				z0 *= f;
				w0 *= f;

			}

		}

		dst[ dstOffset ] = x0;
		dst[ dstOffset + 1 ] = y0;
		dst[ dstOffset + 2 ] = z0;
		dst[ dstOffset + 3 ] = w0;

	}

} );

/**
 * @author mrdoob / http://mrdoob.com/
 * @author *kile / http://kile.stravaganza.org/
 * @author philogb / http://blog.thejit.org/
 * @author mikael emtinger / http://gomo.se/
 * @author egraether / http://egraether.com/
 * @author WestLangley / http://github.com/WestLangley
 */

function Vector3( x, y, z ) {

	this.x = x || 0;
	this.y = y || 0;
	this.z = z || 0;

}

Vector3.prototype = {

	constructor: Vector3,

	isVector3: true,

	set: function ( x, y, z ) {

		this.x = x;
		this.y = y;
		this.z = z;

		return this;

	},

	setScalar: function ( scalar ) {

		this.x = scalar;
		this.y = scalar;
		this.z = scalar;

		return this;

	},

	setX: function ( x ) {

		this.x = x;

		return this;

	},

	setY: function ( y ) {

		this.y = y;

		return this;

	},

	setZ: function ( z ) {

		this.z = z;

		return this;

	},

	setComponent: function ( index, value ) {

		switch ( index ) {

			case 0: this.x = value; break;
			case 1: this.y = value; break;
			case 2: this.z = value; break;
			default: throw new Error( 'index is out of range: ' + index );

		}

		return this;

	},

	getComponent: function ( index ) {

		switch ( index ) {

			case 0: return this.x;
			case 1: return this.y;
			case 2: return this.z;
			default: throw new Error( 'index is out of range: ' + index );

		}

	},

	clone: function () {

		return new this.constructor( this.x, this.y, this.z );

	},

	copy: function ( v ) {

		this.x = v.x;
		this.y = v.y;
		this.z = v.z;

		return this;

	},

	add: function ( v, w ) {

		if ( w !== undefined ) {

			console.warn( 'THREE.Vector3: .add() now only accepts one argument. Use .addVectors( a, b ) instead.' );
			return this.addVectors( v, w );

		}

		this.x += v.x;
		this.y += v.y;
		this.z += v.z;

		return this;

	},

	addScalar: function ( s ) {

		this.x += s;
		this.y += s;
		this.z += s;

		return this;

	},

	addVectors: function ( a, b ) {

		this.x = a.x + b.x;
		this.y = a.y + b.y;
		this.z = a.z + b.z;

		return this;

	},

	addScaledVector: function ( v, s ) {

		this.x += v.x * s;
		this.y += v.y * s;
		this.z += v.z * s;

		return this;

	},

	sub: function ( v, w ) {

		if ( w !== undefined ) {

			console.warn( 'THREE.Vector3: .sub() now only accepts one argument. Use .subVectors( a, b ) instead.' );
			return this.subVectors( v, w );

		}

		this.x -= v.x;
		this.y -= v.y;
		this.z -= v.z;

		return this;

	},

	subScalar: function ( s ) {

		this.x -= s;
		this.y -= s;
		this.z -= s;

		return this;

	},

	subVectors: function ( a, b ) {

		this.x = a.x - b.x;
		this.y = a.y - b.y;
		this.z = a.z - b.z;

		return this;

	},

	multiply: function ( v, w ) {

		if ( w !== undefined ) {

			console.warn( 'THREE.Vector3: .multiply() now only accepts one argument. Use .multiplyVectors( a, b ) instead.' );
			return this.multiplyVectors( v, w );

		}

		this.x *= v.x;
		this.y *= v.y;
		this.z *= v.z;

		return this;

	},

	multiplyScalar: function ( scalar ) {

		if ( isFinite( scalar ) ) {

			this.x *= scalar;
			this.y *= scalar;
			this.z *= scalar;

		} else {

			this.x = 0;
			this.y = 0;
			this.z = 0;

		}

		return this;

	},

	multiplyVectors: function ( a, b ) {

		this.x = a.x * b.x;
		this.y = a.y * b.y;
		this.z = a.z * b.z;

		return this;

	},

	applyEuler: function () {

		var quaternion;

		return function applyEuler( euler ) {

			if ( (euler && euler.isEuler) === false ) {

				console.error( 'THREE.Vector3: .applyEuler() now expects an Euler rotation rather than a Vector3 and order.' );

			}

			if ( quaternion === undefined ) quaternion = new Quaternion();

			return this.applyQuaternion( quaternion.setFromEuler( euler ) );

		};

	}(),

	applyAxisAngle: function () {

		var quaternion;

		return function applyAxisAngle( axis, angle ) {

			if ( quaternion === undefined ) quaternion = new Quaternion();

			return this.applyQuaternion( quaternion.setFromAxisAngle( axis, angle ) );

		};

	}(),

	applyMatrix3: function ( m ) {

		var x = this.x, y = this.y, z = this.z;
		var e = m.elements;

		this.x = e[ 0 ] * x + e[ 3 ] * y + e[ 6 ] * z;
		this.y = e[ 1 ] * x + e[ 4 ] * y + e[ 7 ] * z;
		this.z = e[ 2 ] * x + e[ 5 ] * y + e[ 8 ] * z;

		return this;

	},

	applyMatrix4: function ( m ) {

		var x = this.x, y = this.y, z = this.z;
		var e = m.elements;

		this.x = e[ 0 ] * x + e[ 4 ] * y + e[ 8 ]  * z + e[ 12 ];
		this.y = e[ 1 ] * x + e[ 5 ] * y + e[ 9 ]  * z + e[ 13 ];
		this.z = e[ 2 ] * x + e[ 6 ] * y + e[ 10 ] * z + e[ 14 ];
		var w =  e[ 3 ] * x + e[ 7 ] * y + e[ 11 ] * z + e[ 15 ];

		return this.divideScalar( w );

	},

	applyQuaternion: function ( q ) {

		var x = this.x, y = this.y, z = this.z;
		var qx = q.x, qy = q.y, qz = q.z, qw = q.w;

		// calculate quat * vector

		var ix =  qw * x + qy * z - qz * y;
		var iy =  qw * y + qz * x - qx * z;
		var iz =  qw * z + qx * y - qy * x;
		var iw = - qx * x - qy * y - qz * z;

		// calculate result * inverse quat

		this.x = ix * qw + iw * - qx + iy * - qz - iz * - qy;
		this.y = iy * qw + iw * - qy + iz * - qx - ix * - qz;
		this.z = iz * qw + iw * - qz + ix * - qy - iy * - qx;

		return this;

	},

	project: function () {

		var matrix;

		return function project( camera ) {

			if ( matrix === undefined ) matrix = new Matrix4();

			matrix.multiplyMatrices( camera.projectionMatrix, matrix.getInverse( camera.matrixWorld ) );
			return this.applyMatrix4( matrix );

		};

	}(),

	unproject: function () {

		var matrix;

		return function unproject( camera ) {

			if ( matrix === undefined ) matrix = new Matrix4();

			matrix.multiplyMatrices( camera.matrixWorld, matrix.getInverse( camera.projectionMatrix ) );
			return this.applyMatrix4( matrix );

		};

	}(),

	transformDirection: function ( m ) {

		// input: THREE.Matrix4 affine matrix
		// vector interpreted as a direction

		var x = this.x, y = this.y, z = this.z;
		var e = m.elements;

		this.x = e[ 0 ] * x + e[ 4 ] * y + e[ 8 ]  * z;
		this.y = e[ 1 ] * x + e[ 5 ] * y + e[ 9 ]  * z;
		this.z = e[ 2 ] * x + e[ 6 ] * y + e[ 10 ] * z;

		return this.normalize();

	},

	divide: function ( v ) {

		this.x /= v.x;
		this.y /= v.y;
		this.z /= v.z;

		return this;

	},

	divideScalar: function ( scalar ) {

		return this.multiplyScalar( 1 / scalar );

	},

	min: function ( v ) {

		this.x = Math.min( this.x, v.x );
		this.y = Math.min( this.y, v.y );
		this.z = Math.min( this.z, v.z );

		return this;

	},

	max: function ( v ) {

		this.x = Math.max( this.x, v.x );
		this.y = Math.max( this.y, v.y );
		this.z = Math.max( this.z, v.z );

		return this;

	},

	clamp: function ( min, max ) {

		// This function assumes min < max, if this assumption isn't true it will not operate correctly

		this.x = Math.max( min.x, Math.min( max.x, this.x ) );
		this.y = Math.max( min.y, Math.min( max.y, this.y ) );
		this.z = Math.max( min.z, Math.min( max.z, this.z ) );

		return this;

	},

	clampScalar: function () {

		var min, max;

		return function clampScalar( minVal, maxVal ) {

			if ( min === undefined ) {

				min = new Vector3();
				max = new Vector3();

			}

			min.set( minVal, minVal, minVal );
			max.set( maxVal, maxVal, maxVal );

			return this.clamp( min, max );

		};

	}(),

	clampLength: function ( min, max ) {

		var length = this.length();

		return this.multiplyScalar( Math.max( min, Math.min( max, length ) ) / length );

	},

	floor: function () {

		this.x = Math.floor( this.x );
		this.y = Math.floor( this.y );
		this.z = Math.floor( this.z );

		return this;

	},

	ceil: function () {

		this.x = Math.ceil( this.x );
		this.y = Math.ceil( this.y );
		this.z = Math.ceil( this.z );

		return this;

	},

	round: function () {

		this.x = Math.round( this.x );
		this.y = Math.round( this.y );
		this.z = Math.round( this.z );

		return this;

	},

	roundToZero: function () {

		this.x = ( this.x < 0 ) ? Math.ceil( this.x ) : Math.floor( this.x );
		this.y = ( this.y < 0 ) ? Math.ceil( this.y ) : Math.floor( this.y );
		this.z = ( this.z < 0 ) ? Math.ceil( this.z ) : Math.floor( this.z );

		return this;

	},

	negate: function () {

		this.x = - this.x;
		this.y = - this.y;
		this.z = - this.z;

		return this;

	},

	dot: function ( v ) {

		return this.x * v.x + this.y * v.y + this.z * v.z;

	},

	lengthSq: function () {

		return this.x * this.x + this.y * this.y + this.z * this.z;

	},

	length: function () {

		return Math.sqrt( this.x * this.x + this.y * this.y + this.z * this.z );

	},

	lengthManhattan: function () {

		return Math.abs( this.x ) + Math.abs( this.y ) + Math.abs( this.z );

	},

	normalize: function () {

		return this.divideScalar( this.length() );

	},

	setLength: function ( length ) {

		return this.multiplyScalar( length / this.length() );

	},

	lerp: function ( v, alpha ) {

		this.x += ( v.x - this.x ) * alpha;
		this.y += ( v.y - this.y ) * alpha;
		this.z += ( v.z - this.z ) * alpha;

		return this;

	},

	lerpVectors: function ( v1, v2, alpha ) {

		return this.subVectors( v2, v1 ).multiplyScalar( alpha ).add( v1 );

	},

	cross: function ( v, w ) {

		if ( w !== undefined ) {

			console.warn( 'THREE.Vector3: .cross() now only accepts one argument. Use .crossVectors( a, b ) instead.' );
			return this.crossVectors( v, w );

		}

		var x = this.x, y = this.y, z = this.z;

		this.x = y * v.z - z * v.y;
		this.y = z * v.x - x * v.z;
		this.z = x * v.y - y * v.x;

		return this;

	},

	crossVectors: function ( a, b ) {

		var ax = a.x, ay = a.y, az = a.z;
		var bx = b.x, by = b.y, bz = b.z;

		this.x = ay * bz - az * by;
		this.y = az * bx - ax * bz;
		this.z = ax * by - ay * bx;

		return this;

	},

	projectOnVector: function ( vector ) {

		var scalar = vector.dot( this ) / vector.lengthSq();

		return this.copy( vector ).multiplyScalar( scalar );

	},

	projectOnPlane: function () {

		var v1;

		return function projectOnPlane( planeNormal ) {

			if ( v1 === undefined ) v1 = new Vector3();

			v1.copy( this ).projectOnVector( planeNormal );

			return this.sub( v1 );

		};

	}(),

	reflect: function () {

		// reflect incident vector off plane orthogonal to normal
		// normal is assumed to have unit length

		var v1;

		return function reflect( normal ) {

			if ( v1 === undefined ) v1 = new Vector3();

			return this.sub( v1.copy( normal ).multiplyScalar( 2 * this.dot( normal ) ) );

		};

	}(),

	angleTo: function ( v ) {

		var theta = this.dot( v ) / ( Math.sqrt( this.lengthSq() * v.lengthSq() ) );

		// clamp, to handle numerical problems

		return Math.acos( _Math.clamp( theta, - 1, 1 ) );

	},

	distanceTo: function ( v ) {

		return Math.sqrt( this.distanceToSquared( v ) );

	},

	distanceToSquared: function ( v ) {

		var dx = this.x - v.x, dy = this.y - v.y, dz = this.z - v.z;

		return dx * dx + dy * dy + dz * dz;

	},

	distanceToManhattan: function ( v ) {

		return Math.abs( this.x - v.x ) + Math.abs( this.y - v.y ) + Math.abs( this.z - v.z );

	},

	setFromSpherical: function( s ) {

		var sinPhiRadius = Math.sin( s.phi ) * s.radius;

		this.x = sinPhiRadius * Math.sin( s.theta );
		this.y = Math.cos( s.phi ) * s.radius;
		this.z = sinPhiRadius * Math.cos( s.theta );

		return this;

	},

	setFromCylindrical: function( c ) {

		this.x = c.radius * Math.sin( c.theta );
		this.y = c.y;
		this.z = c.radius * Math.cos( c.theta );

		return this;

	},

	setFromMatrixPosition: function ( m ) {

		return this.setFromMatrixColumn( m, 3 );

	},

	setFromMatrixScale: function ( m ) {

		var sx = this.setFromMatrixColumn( m, 0 ).length();
		var sy = this.setFromMatrixColumn( m, 1 ).length();
		var sz = this.setFromMatrixColumn( m, 2 ).length();

		this.x = sx;
		this.y = sy;
		this.z = sz;

		return this;

	},

	setFromMatrixColumn: function ( m, index ) {

		if ( typeof m === 'number' ) {

			console.warn( 'THREE.Vector3: setFromMatrixColumn now expects ( matrix, index ).' );
			var temp = m;
			m = index;
			index = temp;

		}

		return this.fromArray( m.elements, index * 4 );

	},

	equals: function ( v ) {

		return ( ( v.x === this.x ) && ( v.y === this.y ) && ( v.z === this.z ) );

	},

	fromArray: function ( array, offset ) {

		if ( offset === undefined ) offset = 0;

		this.x = array[ offset ];
		this.y = array[ offset + 1 ];
		this.z = array[ offset + 2 ];

		return this;

	},

	toArray: function ( array, offset ) {

		if ( array === undefined ) array = [];
		if ( offset === undefined ) offset = 0;

		array[ offset ] = this.x;
		array[ offset + 1 ] = this.y;
		array[ offset + 2 ] = this.z;

		return array;

	},

	fromBufferAttribute: function ( attribute, index, offset ) {

		if ( offset !== undefined ) {

			console.warn( 'THREE.Vector3: offset has been removed from .fromBufferAttribute().' );

		}

		this.x = attribute.getX( index );
		this.y = attribute.getY( index );
		this.z = attribute.getZ( index );

		return this;

	}

};

/**
 * @author mrdoob / http://mrdoob.com/
 * @author supereggbert / http://www.paulbrunt.co.uk/
 * @author philogb / http://blog.thejit.org/
 * @author jordi_ros / http://plattsoft.com
 * @author D1plo1d / http://github.com/D1plo1d
 * @author alteredq / http://alteredqualia.com/
 * @author mikael emtinger / http://gomo.se/
 * @author timknip / http://www.floorplanner.com/
 * @author bhouston / http://clara.io
 * @author WestLangley / http://github.com/WestLangley
 */

function Matrix4() {

	this.elements = new Float32Array( [

		1, 0, 0, 0,
		0, 1, 0, 0,
		0, 0, 1, 0,
		0, 0, 0, 1

	] );

	if ( arguments.length > 0 ) {

		console.error( 'THREE.Matrix4: the constructor no longer reads arguments. use .set() instead.' );

	}

}

Matrix4.prototype = {

	constructor: Matrix4,

	isMatrix4: true,

	set: function ( n11, n12, n13, n14, n21, n22, n23, n24, n31, n32, n33, n34, n41, n42, n43, n44 ) {

		var te = this.elements;

		te[ 0 ] = n11; te[ 4 ] = n12; te[ 8 ] = n13; te[ 12 ] = n14;
		te[ 1 ] = n21; te[ 5 ] = n22; te[ 9 ] = n23; te[ 13 ] = n24;
		te[ 2 ] = n31; te[ 6 ] = n32; te[ 10 ] = n33; te[ 14 ] = n34;
		te[ 3 ] = n41; te[ 7 ] = n42; te[ 11 ] = n43; te[ 15 ] = n44;

		return this;

	},

	identity: function () {

		this.set(

			1, 0, 0, 0,
			0, 1, 0, 0,
			0, 0, 1, 0,
			0, 0, 0, 1

		);

		return this;

	},

	clone: function () {

		return new Matrix4().fromArray( this.elements );

	},

	copy: function ( m ) {

		this.elements.set( m.elements );

		return this;

	},

	copyPosition: function ( m ) {

		var te = this.elements;
		var me = m.elements;

		te[ 12 ] = me[ 12 ];
		te[ 13 ] = me[ 13 ];
		te[ 14 ] = me[ 14 ];

		return this;

	},

	extractBasis: function ( xAxis, yAxis, zAxis ) {

		xAxis.setFromMatrixColumn( this, 0 );
		yAxis.setFromMatrixColumn( this, 1 );
		zAxis.setFromMatrixColumn( this, 2 );

		return this;

	},

	makeBasis: function ( xAxis, yAxis, zAxis ) {

		this.set(
			xAxis.x, yAxis.x, zAxis.x, 0,
			xAxis.y, yAxis.y, zAxis.y, 0,
			xAxis.z, yAxis.z, zAxis.z, 0,
			0,       0,       0,       1
		);

		return this;

	},

	extractRotation: function () {

		var v1;

		return function extractRotation( m ) {

			if ( v1 === undefined ) v1 = new Vector3();

			var te = this.elements;
			var me = m.elements;

			var scaleX = 1 / v1.setFromMatrixColumn( m, 0 ).length();
			var scaleY = 1 / v1.setFromMatrixColumn( m, 1 ).length();
			var scaleZ = 1 / v1.setFromMatrixColumn( m, 2 ).length();

			te[ 0 ] = me[ 0 ] * scaleX;
			te[ 1 ] = me[ 1 ] * scaleX;
			te[ 2 ] = me[ 2 ] * scaleX;

			te[ 4 ] = me[ 4 ] * scaleY;
			te[ 5 ] = me[ 5 ] * scaleY;
			te[ 6 ] = me[ 6 ] * scaleY;

			te[ 8 ] = me[ 8 ] * scaleZ;
			te[ 9 ] = me[ 9 ] * scaleZ;
			te[ 10 ] = me[ 10 ] * scaleZ;

			return this;

		};

	}(),

	makeRotationFromEuler: function ( euler ) {

		if ( (euler && euler.isEuler) === false ) {

			console.error( 'THREE.Matrix: .makeRotationFromEuler() now expects a Euler rotation rather than a Vector3 and order.' );

		}

		var te = this.elements;

		var x = euler.x, y = euler.y, z = euler.z;
		var a = Math.cos( x ), b = Math.sin( x );
		var c = Math.cos( y ), d = Math.sin( y );
		var e = Math.cos( z ), f = Math.sin( z );

		if ( euler.order === 'XYZ' ) {

			var ae = a * e, af = a * f, be = b * e, bf = b * f;

			te[ 0 ] = c * e;
			te[ 4 ] = - c * f;
			te[ 8 ] = d;

			te[ 1 ] = af + be * d;
			te[ 5 ] = ae - bf * d;
			te[ 9 ] = - b * c;

			te[ 2 ] = bf - ae * d;
			te[ 6 ] = be + af * d;
			te[ 10 ] = a * c;

		} else if ( euler.order === 'YXZ' ) {

			var ce = c * e, cf = c * f, de = d * e, df = d * f;

			te[ 0 ] = ce + df * b;
			te[ 4 ] = de * b - cf;
			te[ 8 ] = a * d;

			te[ 1 ] = a * f;
			te[ 5 ] = a * e;
			te[ 9 ] = - b;

			te[ 2 ] = cf * b - de;
			te[ 6 ] = df + ce * b;
			te[ 10 ] = a * c;

		} else if ( euler.order === 'ZXY' ) {

			var ce = c * e, cf = c * f, de = d * e, df = d * f;

			te[ 0 ] = ce - df * b;
			te[ 4 ] = - a * f;
			te[ 8 ] = de + cf * b;

			te[ 1 ] = cf + de * b;
			te[ 5 ] = a * e;
			te[ 9 ] = df - ce * b;

			te[ 2 ] = - a * d;
			te[ 6 ] = b;
			te[ 10 ] = a * c;

		} else if ( euler.order === 'ZYX' ) {

			var ae = a * e, af = a * f, be = b * e, bf = b * f;

			te[ 0 ] = c * e;
			te[ 4 ] = be * d - af;
			te[ 8 ] = ae * d + bf;

			te[ 1 ] = c * f;
			te[ 5 ] = bf * d + ae;
			te[ 9 ] = af * d - be;

			te[ 2 ] = - d;
			te[ 6 ] = b * c;
			te[ 10 ] = a * c;

		} else if ( euler.order === 'YZX' ) {

			var ac = a * c, ad = a * d, bc = b * c, bd = b * d;

			te[ 0 ] = c * e;
			te[ 4 ] = bd - ac * f;
			te[ 8 ] = bc * f + ad;

			te[ 1 ] = f;
			te[ 5 ] = a * e;
			te[ 9 ] = - b * e;

			te[ 2 ] = - d * e;
			te[ 6 ] = ad * f + bc;
			te[ 10 ] = ac - bd * f;

		} else if ( euler.order === 'XZY' ) {

			var ac = a * c, ad = a * d, bc = b * c, bd = b * d;

			te[ 0 ] = c * e;
			te[ 4 ] = - f;
			te[ 8 ] = d * e;

			te[ 1 ] = ac * f + bd;
			te[ 5 ] = a * e;
			te[ 9 ] = ad * f - bc;

			te[ 2 ] = bc * f - ad;
			te[ 6 ] = b * e;
			te[ 10 ] = bd * f + ac;

		}

		// last column
		te[ 3 ] = 0;
		te[ 7 ] = 0;
		te[ 11 ] = 0;

		// bottom row
		te[ 12 ] = 0;
		te[ 13 ] = 0;
		te[ 14 ] = 0;
		te[ 15 ] = 1;

		return this;

	},

	makeRotationFromQuaternion: function ( q ) {

		var te = this.elements;

		var x = q.x, y = q.y, z = q.z, w = q.w;
		var x2 = x + x, y2 = y + y, z2 = z + z;
		var xx = x * x2, xy = x * y2, xz = x * z2;
		var yy = y * y2, yz = y * z2, zz = z * z2;
		var wx = w * x2, wy = w * y2, wz = w * z2;

		te[ 0 ] = 1 - ( yy + zz );
		te[ 4 ] = xy - wz;
		te[ 8 ] = xz + wy;

		te[ 1 ] = xy + wz;
		te[ 5 ] = 1 - ( xx + zz );
		te[ 9 ] = yz - wx;

		te[ 2 ] = xz - wy;
		te[ 6 ] = yz + wx;
		te[ 10 ] = 1 - ( xx + yy );

		// last column
		te[ 3 ] = 0;
		te[ 7 ] = 0;
		te[ 11 ] = 0;

		// bottom row
		te[ 12 ] = 0;
		te[ 13 ] = 0;
		te[ 14 ] = 0;
		te[ 15 ] = 1;

		return this;

	},

	lookAt: function () {

		var x, y, z;

		return function lookAt( eye, target, up ) {

			if ( x === undefined ) {

				x = new Vector3();
				y = new Vector3();
				z = new Vector3();

			}

			var te = this.elements;

			z.subVectors( eye, target ).normalize();

			if ( z.lengthSq() === 0 ) {

				z.z = 1;

			}

			x.crossVectors( up, z ).normalize();

			if ( x.lengthSq() === 0 ) {

				z.z += 0.0001;
				x.crossVectors( up, z ).normalize();

			}

			y.crossVectors( z, x );


			te[ 0 ] = x.x; te[ 4 ] = y.x; te[ 8 ] = z.x;
			te[ 1 ] = x.y; te[ 5 ] = y.y; te[ 9 ] = z.y;
			te[ 2 ] = x.z; te[ 6 ] = y.z; te[ 10 ] = z.z;

			return this;

		};

	}(),

	multiply: function ( m, n ) {

		if ( n !== undefined ) {

			console.warn( 'THREE.Matrix4: .multiply() now only accepts one argument. Use .multiplyMatrices( a, b ) instead.' );
			return this.multiplyMatrices( m, n );

		}

		return this.multiplyMatrices( this, m );

	},

	premultiply: function ( m ) {

		return this.multiplyMatrices( m, this );

	},

	multiplyMatrices: function ( a, b ) {

		var ae = a.elements;
		var be = b.elements;
		var te = this.elements;

		var a11 = ae[ 0 ], a12 = ae[ 4 ], a13 = ae[ 8 ], a14 = ae[ 12 ];
		var a21 = ae[ 1 ], a22 = ae[ 5 ], a23 = ae[ 9 ], a24 = ae[ 13 ];
		var a31 = ae[ 2 ], a32 = ae[ 6 ], a33 = ae[ 10 ], a34 = ae[ 14 ];
		var a41 = ae[ 3 ], a42 = ae[ 7 ], a43 = ae[ 11 ], a44 = ae[ 15 ];

		var b11 = be[ 0 ], b12 = be[ 4 ], b13 = be[ 8 ], b14 = be[ 12 ];
		var b21 = be[ 1 ], b22 = be[ 5 ], b23 = be[ 9 ], b24 = be[ 13 ];
		var b31 = be[ 2 ], b32 = be[ 6 ], b33 = be[ 10 ], b34 = be[ 14 ];
		var b41 = be[ 3 ], b42 = be[ 7 ], b43 = be[ 11 ], b44 = be[ 15 ];

		te[ 0 ] = a11 * b11 + a12 * b21 + a13 * b31 + a14 * b41;
		te[ 4 ] = a11 * b12 + a12 * b22 + a13 * b32 + a14 * b42;
		te[ 8 ] = a11 * b13 + a12 * b23 + a13 * b33 + a14 * b43;
		te[ 12 ] = a11 * b14 + a12 * b24 + a13 * b34 + a14 * b44;

		te[ 1 ] = a21 * b11 + a22 * b21 + a23 * b31 + a24 * b41;
		te[ 5 ] = a21 * b12 + a22 * b22 + a23 * b32 + a24 * b42;
		te[ 9 ] = a21 * b13 + a22 * b23 + a23 * b33 + a24 * b43;
		te[ 13 ] = a21 * b14 + a22 * b24 + a23 * b34 + a24 * b44;

		te[ 2 ] = a31 * b11 + a32 * b21 + a33 * b31 + a34 * b41;
		te[ 6 ] = a31 * b12 + a32 * b22 + a33 * b32 + a34 * b42;
		te[ 10 ] = a31 * b13 + a32 * b23 + a33 * b33 + a34 * b43;
		te[ 14 ] = a31 * b14 + a32 * b24 + a33 * b34 + a34 * b44;

		te[ 3 ] = a41 * b11 + a42 * b21 + a43 * b31 + a44 * b41;
		te[ 7 ] = a41 * b12 + a42 * b22 + a43 * b32 + a44 * b42;
		te[ 11 ] = a41 * b13 + a42 * b23 + a43 * b33 + a44 * b43;
		te[ 15 ] = a41 * b14 + a42 * b24 + a43 * b34 + a44 * b44;

		return this;

	},

	multiplyToArray: function ( a, b, r ) {

		var te = this.elements;

		this.multiplyMatrices( a, b );

		r[ 0 ] = te[ 0 ]; r[ 1 ] = te[ 1 ]; r[ 2 ] = te[ 2 ]; r[ 3 ] = te[ 3 ];
		r[ 4 ] = te[ 4 ]; r[ 5 ] = te[ 5 ]; r[ 6 ] = te[ 6 ]; r[ 7 ] = te[ 7 ];
		r[ 8 ]  = te[ 8 ]; r[ 9 ]  = te[ 9 ]; r[ 10 ] = te[ 10 ]; r[ 11 ] = te[ 11 ];
		r[ 12 ] = te[ 12 ]; r[ 13 ] = te[ 13 ]; r[ 14 ] = te[ 14 ]; r[ 15 ] = te[ 15 ];

		return this;

	},

	multiplyScalar: function ( s ) {

		var te = this.elements;

		te[ 0 ] *= s; te[ 4 ] *= s; te[ 8 ] *= s; te[ 12 ] *= s;
		te[ 1 ] *= s; te[ 5 ] *= s; te[ 9 ] *= s; te[ 13 ] *= s;
		te[ 2 ] *= s; te[ 6 ] *= s; te[ 10 ] *= s; te[ 14 ] *= s;
		te[ 3 ] *= s; te[ 7 ] *= s; te[ 11 ] *= s; te[ 15 ] *= s;

		return this;

	},

	applyToBufferAttribute: function () {

		var v1;

		return function applyToBufferAttribute( attribute ) {

			if ( v1 === undefined ) v1 = new Vector3();

			for ( var i = 0, l = attribute.count; i < l; i ++ ) {

				v1.x = attribute.getX( i );
				v1.y = attribute.getY( i );
				v1.z = attribute.getZ( i );

				v1.applyMatrix4( this );

				attribute.setXYZ( i, v1.x, v1.y, v1.z );

			}

			return attribute;

		};

	}(),

	determinant: function () {

		var te = this.elements;

		var n11 = te[ 0 ], n12 = te[ 4 ], n13 = te[ 8 ], n14 = te[ 12 ];
		var n21 = te[ 1 ], n22 = te[ 5 ], n23 = te[ 9 ], n24 = te[ 13 ];
		var n31 = te[ 2 ], n32 = te[ 6 ], n33 = te[ 10 ], n34 = te[ 14 ];
		var n41 = te[ 3 ], n42 = te[ 7 ], n43 = te[ 11 ], n44 = te[ 15 ];

		//TODO: make this more efficient
		//( based on http://www.euclideanspace.com/maths/algebra/matrix/functions/inverse/fourD/index.htm )

		return (
			n41 * (
				+ n14 * n23 * n32
				 - n13 * n24 * n32
				 - n14 * n22 * n33
				 + n12 * n24 * n33
				 + n13 * n22 * n34
				 - n12 * n23 * n34
			) +
			n42 * (
				+ n11 * n23 * n34
				 - n11 * n24 * n33
				 + n14 * n21 * n33
				 - n13 * n21 * n34
				 + n13 * n24 * n31
				 - n14 * n23 * n31
			) +
			n43 * (
				+ n11 * n24 * n32
				 - n11 * n22 * n34
				 - n14 * n21 * n32
				 + n12 * n21 * n34
				 + n14 * n22 * n31
				 - n12 * n24 * n31
			) +
			n44 * (
				- n13 * n22 * n31
				 - n11 * n23 * n32
				 + n11 * n22 * n33
				 + n13 * n21 * n32
				 - n12 * n21 * n33
				 + n12 * n23 * n31
			)

		);

	},

	transpose: function () {

		var te = this.elements;
		var tmp;

		tmp = te[ 1 ]; te[ 1 ] = te[ 4 ]; te[ 4 ] = tmp;
		tmp = te[ 2 ]; te[ 2 ] = te[ 8 ]; te[ 8 ] = tmp;
		tmp = te[ 6 ]; te[ 6 ] = te[ 9 ]; te[ 9 ] = tmp;

		tmp = te[ 3 ]; te[ 3 ] = te[ 12 ]; te[ 12 ] = tmp;
		tmp = te[ 7 ]; te[ 7 ] = te[ 13 ]; te[ 13 ] = tmp;
		tmp = te[ 11 ]; te[ 11 ] = te[ 14 ]; te[ 14 ] = tmp;

		return this;

	},

	setPosition: function ( v ) {

		var te = this.elements;

		te[ 12 ] = v.x;
		te[ 13 ] = v.y;
		te[ 14 ] = v.z;

		return this;

	},

	getInverse: function ( m, throwOnDegenerate ) {

		// based on http://www.euclideanspace.com/maths/algebra/matrix/functions/inverse/fourD/index.htm
		var te = this.elements,
			me = m.elements,

			n11 = me[ 0 ], n21 = me[ 1 ], n31 = me[ 2 ], n41 = me[ 3 ],
			n12 = me[ 4 ], n22 = me[ 5 ], n32 = me[ 6 ], n42 = me[ 7 ],
			n13 = me[ 8 ], n23 = me[ 9 ], n33 = me[ 10 ], n43 = me[ 11 ],
			n14 = me[ 12 ], n24 = me[ 13 ], n34 = me[ 14 ], n44 = me[ 15 ],

			t11 = n23 * n34 * n42 - n24 * n33 * n42 + n24 * n32 * n43 - n22 * n34 * n43 - n23 * n32 * n44 + n22 * n33 * n44,
			t12 = n14 * n33 * n42 - n13 * n34 * n42 - n14 * n32 * n43 + n12 * n34 * n43 + n13 * n32 * n44 - n12 * n33 * n44,
			t13 = n13 * n24 * n42 - n14 * n23 * n42 + n14 * n22 * n43 - n12 * n24 * n43 - n13 * n22 * n44 + n12 * n23 * n44,
			t14 = n14 * n23 * n32 - n13 * n24 * n32 - n14 * n22 * n33 + n12 * n24 * n33 + n13 * n22 * n34 - n12 * n23 * n34;

		var det = n11 * t11 + n21 * t12 + n31 * t13 + n41 * t14;

		if ( det === 0 ) {

			var msg = "THREE.Matrix4.getInverse(): can't invert matrix, determinant is 0";

			if ( throwOnDegenerate === true ) {

				throw new Error( msg );

			} else {

				console.warn( msg );

			}

			return this.identity();

		}

		var detInv = 1 / det;

		te[ 0 ] = t11 * detInv;
		te[ 1 ] = ( n24 * n33 * n41 - n23 * n34 * n41 - n24 * n31 * n43 + n21 * n34 * n43 + n23 * n31 * n44 - n21 * n33 * n44 ) * detInv;
		te[ 2 ] = ( n22 * n34 * n41 - n24 * n32 * n41 + n24 * n31 * n42 - n21 * n34 * n42 - n22 * n31 * n44 + n21 * n32 * n44 ) * detInv;
		te[ 3 ] = ( n23 * n32 * n41 - n22 * n33 * n41 - n23 * n31 * n42 + n21 * n33 * n42 + n22 * n31 * n43 - n21 * n32 * n43 ) * detInv;

		te[ 4 ] = t12 * detInv;
		te[ 5 ] = ( n13 * n34 * n41 - n14 * n33 * n41 + n14 * n31 * n43 - n11 * n34 * n43 - n13 * n31 * n44 + n11 * n33 * n44 ) * detInv;
		te[ 6 ] = ( n14 * n32 * n41 - n12 * n34 * n41 - n14 * n31 * n42 + n11 * n34 * n42 + n12 * n31 * n44 - n11 * n32 * n44 ) * detInv;
		te[ 7 ] = ( n12 * n33 * n41 - n13 * n32 * n41 + n13 * n31 * n42 - n11 * n33 * n42 - n12 * n31 * n43 + n11 * n32 * n43 ) * detInv;

		te[ 8 ] = t13 * detInv;
		te[ 9 ] = ( n14 * n23 * n41 - n13 * n24 * n41 - n14 * n21 * n43 + n11 * n24 * n43 + n13 * n21 * n44 - n11 * n23 * n44 ) * detInv;
		te[ 10 ] = ( n12 * n24 * n41 - n14 * n22 * n41 + n14 * n21 * n42 - n11 * n24 * n42 - n12 * n21 * n44 + n11 * n22 * n44 ) * detInv;
		te[ 11 ] = ( n13 * n22 * n41 - n12 * n23 * n41 - n13 * n21 * n42 + n11 * n23 * n42 + n12 * n21 * n43 - n11 * n22 * n43 ) * detInv;

		te[ 12 ] = t14 * detInv;
		te[ 13 ] = ( n13 * n24 * n31 - n14 * n23 * n31 + n14 * n21 * n33 - n11 * n24 * n33 - n13 * n21 * n34 + n11 * n23 * n34 ) * detInv;
		te[ 14 ] = ( n14 * n22 * n31 - n12 * n24 * n31 - n14 * n21 * n32 + n11 * n24 * n32 + n12 * n21 * n34 - n11 * n22 * n34 ) * detInv;
		te[ 15 ] = ( n12 * n23 * n31 - n13 * n22 * n31 + n13 * n21 * n32 - n11 * n23 * n32 - n12 * n21 * n33 + n11 * n22 * n33 ) * detInv;

		return this;

	},

	scale: function ( v ) {

		var te = this.elements;
		var x = v.x, y = v.y, z = v.z;

		te[ 0 ] *= x; te[ 4 ] *= y; te[ 8 ] *= z;
		te[ 1 ] *= x; te[ 5 ] *= y; te[ 9 ] *= z;
		te[ 2 ] *= x; te[ 6 ] *= y; te[ 10 ] *= z;
		te[ 3 ] *= x; te[ 7 ] *= y; te[ 11 ] *= z;

		return this;

	},

	getMaxScaleOnAxis: function () {

		var te = this.elements;

		var scaleXSq = te[ 0 ] * te[ 0 ] + te[ 1 ] * te[ 1 ] + te[ 2 ] * te[ 2 ];
		var scaleYSq = te[ 4 ] * te[ 4 ] + te[ 5 ] * te[ 5 ] + te[ 6 ] * te[ 6 ];
		var scaleZSq = te[ 8 ] * te[ 8 ] + te[ 9 ] * te[ 9 ] + te[ 10 ] * te[ 10 ];

		return Math.sqrt( Math.max( scaleXSq, scaleYSq, scaleZSq ) );

	},

	makeTranslation: function ( x, y, z ) {

		this.set(

			1, 0, 0, x,
			0, 1, 0, y,
			0, 0, 1, z,
			0, 0, 0, 1

		);

		return this;

	},

	makeRotationX: function ( theta ) {

		var c = Math.cos( theta ), s = Math.sin( theta );

		this.set(

			1, 0,  0, 0,
			0, c, - s, 0,
			0, s,  c, 0,
			0, 0,  0, 1

		);

		return this;

	},

	makeRotationY: function ( theta ) {

		var c = Math.cos( theta ), s = Math.sin( theta );

		this.set(

			 c, 0, s, 0,
			 0, 1, 0, 0,
			- s, 0, c, 0,
			 0, 0, 0, 1

		);

		return this;

	},

	makeRotationZ: function ( theta ) {

		var c = Math.cos( theta ), s = Math.sin( theta );

		this.set(

			c, - s, 0, 0,
			s,  c, 0, 0,
			0,  0, 1, 0,
			0,  0, 0, 1

		);

		return this;

	},

	makeRotationAxis: function ( axis, angle ) {

		// Based on http://www.gamedev.net/reference/articles/article1199.asp

		var c = Math.cos( angle );
		var s = Math.sin( angle );
		var t = 1 - c;
		var x = axis.x, y = axis.y, z = axis.z;
		var tx = t * x, ty = t * y;

		this.set(

			tx * x + c, tx * y - s * z, tx * z + s * y, 0,
			tx * y + s * z, ty * y + c, ty * z - s * x, 0,
			tx * z - s * y, ty * z + s * x, t * z * z + c, 0,
			0, 0, 0, 1

		);

		 return this;

	},

	makeScale: function ( x, y, z ) {

		this.set(

			x, 0, 0, 0,
			0, y, 0, 0,
			0, 0, z, 0,
			0, 0, 0, 1

		);

		return this;

	},

	makeShear: function ( x, y, z ) {

		this.set(

			1, y, z, 0,
			x, 1, z, 0,
			x, y, 1, 0,
			0, 0, 0, 1

		);

		return this;

	},

	compose: function ( position, quaternion, scale ) {

		this.makeRotationFromQuaternion( quaternion );
		this.scale( scale );
		this.setPosition( position );

		return this;

	},

	decompose: function () {

		var vector, matrix;

		return function decompose( position, quaternion, scale ) {

			if ( vector === undefined ) {

				vector = new Vector3();
				matrix = new Matrix4();

			}

			var te = this.elements;

			var sx = vector.set( te[ 0 ], te[ 1 ], te[ 2 ] ).length();
			var sy = vector.set( te[ 4 ], te[ 5 ], te[ 6 ] ).length();
			var sz = vector.set( te[ 8 ], te[ 9 ], te[ 10 ] ).length();

			// if determine is negative, we need to invert one scale
			var det = this.determinant();
			if ( det < 0 ) {

				sx = - sx;

			}

			position.x = te[ 12 ];
			position.y = te[ 13 ];
			position.z = te[ 14 ];

			// scale the rotation part

			matrix.elements.set( this.elements ); // at this point matrix is incomplete so we can't use .copy()

			var invSX = 1 / sx;
			var invSY = 1 / sy;
			var invSZ = 1 / sz;

			matrix.elements[ 0 ] *= invSX;
			matrix.elements[ 1 ] *= invSX;
			matrix.elements[ 2 ] *= invSX;

			matrix.elements[ 4 ] *= invSY;
			matrix.elements[ 5 ] *= invSY;
			matrix.elements[ 6 ] *= invSY;

			matrix.elements[ 8 ] *= invSZ;
			matrix.elements[ 9 ] *= invSZ;
			matrix.elements[ 10 ] *= invSZ;

			quaternion.setFromRotationMatrix( matrix );

			scale.x = sx;
			scale.y = sy;
			scale.z = sz;

			return this;

		};

	}(),

	makePerspective: function ( left, right, top, bottom, near, far ) {

		if ( far === undefined ) {

			console.warn( 'THREE.Matrix4: .makePerspective() has been redefined and has a new signature. Please check the docs.' );

		}

		var te = this.elements;
		var x = 2 * near / ( right - left );
		var y = 2 * near / ( top - bottom );

		var a = ( right + left ) / ( right - left );
		var b = ( top + bottom ) / ( top - bottom );
		var c = - ( far + near ) / ( far - near );
		var d = - 2 * far * near / ( far - near );

		te[ 0 ] = x;	te[ 4 ] = 0;	te[ 8 ] = a;	te[ 12 ] = 0;
		te[ 1 ] = 0;	te[ 5 ] = y;	te[ 9 ] = b;	te[ 13 ] = 0;
		te[ 2 ] = 0;	te[ 6 ] = 0;	te[ 10 ] = c;	te[ 14 ] = d;
		te[ 3 ] = 0;	te[ 7 ] = 0;	te[ 11 ] = - 1;	te[ 15 ] = 0;

		return this;

	},

	makeOrthographic: function ( left, right, top, bottom, near, far ) {

		var te = this.elements;
		var w = 1.0 / ( right - left );
		var h = 1.0 / ( top - bottom );
		var p = 1.0 / ( far - near );

		var x = ( right + left ) * w;
		var y = ( top + bottom ) * h;
		var z = ( far + near ) * p;

		te[ 0 ] = 2 * w;	te[ 4 ] = 0;	te[ 8 ] = 0;	te[ 12 ] = - x;
		te[ 1 ] = 0;	te[ 5 ] = 2 * h;	te[ 9 ] = 0;	te[ 13 ] = - y;
		te[ 2 ] = 0;	te[ 6 ] = 0;	te[ 10 ] = - 2 * p;	te[ 14 ] = - z;
		te[ 3 ] = 0;	te[ 7 ] = 0;	te[ 11 ] = 0;	te[ 15 ] = 1;

		return this;

	},

	equals: function ( matrix ) {

		var te = this.elements;
		var me = matrix.elements;

		for ( var i = 0; i < 16; i ++ ) {

			if ( te[ i ] !== me[ i ] ) return false;

		}

		return true;

	},

	fromArray: function ( array, offset ) {

		if ( offset === undefined ) offset = 0;

		for( var i = 0; i < 16; i ++ ) {

			this.elements[ i ] = array[ i + offset ];

		}

		return this;

	},

	toArray: function ( array, offset ) {

		if ( array === undefined ) array = [];
		if ( offset === undefined ) offset = 0;

		var te = this.elements;

		array[ offset ] = te[ 0 ];
		array[ offset + 1 ] = te[ 1 ];
		array[ offset + 2 ] = te[ 2 ];
		array[ offset + 3 ] = te[ 3 ];

		array[ offset + 4 ] = te[ 4 ];
		array[ offset + 5 ] = te[ 5 ];
		array[ offset + 6 ] = te[ 6 ];
		array[ offset + 7 ] = te[ 7 ];

		array[ offset + 8 ]  = te[ 8 ];
		array[ offset + 9 ]  = te[ 9 ];
		array[ offset + 10 ] = te[ 10 ];
		array[ offset + 11 ] = te[ 11 ];

		array[ offset + 12 ] = te[ 12 ];
		array[ offset + 13 ] = te[ 13 ];
		array[ offset + 14 ] = te[ 14 ];
		array[ offset + 15 ] = te[ 15 ];

		return array;

	}

};

/**
 * https://github.com/mrdoob/eventdispatcher.js/
 */

function EventDispatcher() {}

EventDispatcher.prototype = {

	addEventListener: function ( type, listener ) {

		if ( this._listeners === undefined ) this._listeners = {};

		var listeners = this._listeners;

		if ( listeners[ type ] === undefined ) {

			listeners[ type ] = [];

		}

		if ( listeners[ type ].indexOf( listener ) === - 1 ) {

			listeners[ type ].push( listener );

		}

	},

	hasEventListener: function ( type, listener ) {

		if ( this._listeners === undefined ) return false;

		var listeners = this._listeners;

		return listeners[ type ] !== undefined && listeners[ type ].indexOf( listener ) !== - 1;

	},

	removeEventListener: function ( type, listener ) {

		if ( this._listeners === undefined ) return;

		var listeners = this._listeners;
		var listenerArray = listeners[ type ];

		if ( listenerArray !== undefined ) {

			var index = listenerArray.indexOf( listener );

			if ( index !== - 1 ) {

				listenerArray.splice( index, 1 );

			}

		}

	},

	dispatchEvent: function ( event ) {

		if ( this._listeners === undefined ) return;

		var listeners = this._listeners;
		var listenerArray = listeners[ event.type ];

		if ( listenerArray !== undefined ) {

			event.target = this;

			var array = [], i = 0;
			var length = listenerArray.length;

			for ( i = 0; i < length; i ++ ) {

				array[ i ] = listenerArray[ i ];

			}

			for ( i = 0; i < length; i ++ ) {

				array[ i ].call( this, event );

			}

		}

	}

};

/**
 * @author mrdoob / http://mrdoob.com/
 * @author philogb / http://blog.thejit.org/
 * @author egraether / http://egraether.com/
 * @author zz85 / http://www.lab4games.net/zz85/blog
 */

function Vector2( x, y ) {

	this.x = x || 0;
	this.y = y || 0;

}

Vector2.prototype = {

	constructor: Vector2,

	isVector2: true,

	get width() {

		return this.x;

	},

	set width( value ) {

		this.x = value;

	},

	get height() {

		return this.y;

	},

	set height( value ) {

		this.y = value;

	},

	//

	set: function ( x, y ) {

		this.x = x;
		this.y = y;

		return this;

	},

	setScalar: function ( scalar ) {

		this.x = scalar;
		this.y = scalar;

		return this;

	},

	setX: function ( x ) {

		this.x = x;

		return this;

	},

	setY: function ( y ) {

		this.y = y;

		return this;

	},

	setComponent: function ( index, value ) {

		switch ( index ) {

			case 0: this.x = value; break;
			case 1: this.y = value; break;
			default: throw new Error( 'index is out of range: ' + index );

		}

		return this;

	},

	getComponent: function ( index ) {

		switch ( index ) {

			case 0: return this.x;
			case 1: return this.y;
			default: throw new Error( 'index is out of range: ' + index );

		}

	},

	clone: function () {

		return new this.constructor( this.x, this.y );

	},

	copy: function ( v ) {

		this.x = v.x;
		this.y = v.y;

		return this;

	},

	add: function ( v, w ) {

		if ( w !== undefined ) {

			console.warn( 'THREE.Vector2: .add() now only accepts one argument. Use .addVectors( a, b ) instead.' );
			return this.addVectors( v, w );

		}

		this.x += v.x;
		this.y += v.y;

		return this;

	},

	addScalar: function ( s ) {

		this.x += s;
		this.y += s;

		return this;

	},

	addVectors: function ( a, b ) {

		this.x = a.x + b.x;
		this.y = a.y + b.y;

		return this;

	},

	addScaledVector: function ( v, s ) {

		this.x += v.x * s;
		this.y += v.y * s;

		return this;

	},

	sub: function ( v, w ) {

		if ( w !== undefined ) {

			console.warn( 'THREE.Vector2: .sub() now only accepts one argument. Use .subVectors( a, b ) instead.' );
			return this.subVectors( v, w );

		}

		this.x -= v.x;
		this.y -= v.y;

		return this;

	},

	subScalar: function ( s ) {

		this.x -= s;
		this.y -= s;

		return this;

	},

	subVectors: function ( a, b ) {

		this.x = a.x - b.x;
		this.y = a.y - b.y;

		return this;

	},

	multiply: function ( v ) {

		this.x *= v.x;
		this.y *= v.y;

		return this;

	},

	multiplyScalar: function ( scalar ) {

		if ( isFinite( scalar ) ) {

			this.x *= scalar;
			this.y *= scalar;

		} else {

			this.x = 0;
			this.y = 0;

		}

		return this;

	},

	divide: function ( v ) {

		this.x /= v.x;
		this.y /= v.y;

		return this;

	},

	divideScalar: function ( scalar ) {

		return this.multiplyScalar( 1 / scalar );

	},

	min: function ( v ) {

		this.x = Math.min( this.x, v.x );
		this.y = Math.min( this.y, v.y );

		return this;

	},

	max: function ( v ) {

		this.x = Math.max( this.x, v.x );
		this.y = Math.max( this.y, v.y );

		return this;

	},

	clamp: function ( min, max ) {

		// This function assumes min < max, if this assumption isn't true it will not operate correctly

		this.x = Math.max( min.x, Math.min( max.x, this.x ) );
		this.y = Math.max( min.y, Math.min( max.y, this.y ) );

		return this;

	},

	clampScalar: function () {

		var min, max;

		return function clampScalar( minVal, maxVal ) {

			if ( min === undefined ) {

				min = new Vector2();
				max = new Vector2();

			}

			min.set( minVal, minVal );
			max.set( maxVal, maxVal );

			return this.clamp( min, max );

		};

	}(),

	clampLength: function ( min, max ) {

		var length = this.length();

		return this.multiplyScalar( Math.max( min, Math.min( max, length ) ) / length );

	},

	floor: function () {

		this.x = Math.floor( this.x );
		this.y = Math.floor( this.y );

		return this;

	},

	ceil: function () {

		this.x = Math.ceil( this.x );
		this.y = Math.ceil( this.y );

		return this;

	},

	round: function () {

		this.x = Math.round( this.x );
		this.y = Math.round( this.y );

		return this;

	},

	roundToZero: function () {

		this.x = ( this.x < 0 ) ? Math.ceil( this.x ) : Math.floor( this.x );
		this.y = ( this.y < 0 ) ? Math.ceil( this.y ) : Math.floor( this.y );

		return this;

	},

	negate: function () {

		this.x = - this.x;
		this.y = - this.y;

		return this;

	},

	dot: function ( v ) {

		return this.x * v.x + this.y * v.y;

	},

	lengthSq: function () {

		return this.x * this.x + this.y * this.y;

	},

	length: function () {

		return Math.sqrt( this.x * this.x + this.y * this.y );

	},

	lengthManhattan: function() {

		return Math.abs( this.x ) + Math.abs( this.y );

	},

	normalize: function () {

		return this.divideScalar( this.length() );

	},

	angle: function () {

		// computes the angle in radians with respect to the positive x-axis

		var angle = Math.atan2( this.y, this.x );

		if ( angle < 0 ) angle += 2 * Math.PI;

		return angle;

	},

	distanceTo: function ( v ) {

		return Math.sqrt( this.distanceToSquared( v ) );

	},

	distanceToSquared: function ( v ) {

		var dx = this.x - v.x, dy = this.y - v.y;
		return dx * dx + dy * dy;

	},

	distanceToManhattan: function ( v ) {

		return Math.abs( this.x - v.x ) + Math.abs( this.y - v.y );

	},

	setLength: function ( length ) {

		return this.multiplyScalar( length / this.length() );

	},

	lerp: function ( v, alpha ) {

		this.x += ( v.x - this.x ) * alpha;
		this.y += ( v.y - this.y ) * alpha;

		return this;

	},

	lerpVectors: function ( v1, v2, alpha ) {

		return this.subVectors( v2, v1 ).multiplyScalar( alpha ).add( v1 );

	},

	equals: function ( v ) {

		return ( ( v.x === this.x ) && ( v.y === this.y ) );

	},

	fromArray: function ( array, offset ) {

		if ( offset === undefined ) offset = 0;

		this.x = array[ offset ];
		this.y = array[ offset + 1 ];

		return this;

	},

	toArray: function ( array, offset ) {

		if ( array === undefined ) array = [];
		if ( offset === undefined ) offset = 0;

		array[ offset ] = this.x;
		array[ offset + 1 ] = this.y;

		return array;

	},

	fromBufferAttribute: function ( attribute, index, offset ) {

		if ( offset !== undefined ) {

			console.warn( 'THREE.Vector2: offset has been removed from .fromBufferAttribute().' );

		}

		this.x = attribute.getX( index );
		this.y = attribute.getY( index );

		return this;

	},

	rotateAround: function ( center, angle ) {

		var c = Math.cos( angle ), s = Math.sin( angle );

		var x = this.x - center.x;
		var y = this.y - center.y;

		this.x = x * c - y * s + center.x;
		this.y = x * s + y * c + center.y;

		return this;

	}

};

/**
 * @author mrdoob / http://mrdoob.com/
 * @author alteredq / http://alteredqualia.com/
 * @author szimek / https://github.com/szimek/
 */

var textureId = 0;

function Texture( image, mapping, wrapS, wrapT, magFilter, minFilter, format, type, anisotropy, encoding ) {

	Object.defineProperty( this, 'id', { value: textureId ++ } );

	this.uuid = _Math.generateUUID();

	this.name = '';

	this.image = image !== undefined ? image : Texture.DEFAULT_IMAGE;
	this.mipmaps = [];

	this.mapping = mapping !== undefined ? mapping : Texture.DEFAULT_MAPPING;

	this.wrapS = wrapS !== undefined ? wrapS : ClampToEdgeWrapping;
	this.wrapT = wrapT !== undefined ? wrapT : ClampToEdgeWrapping;

	this.magFilter = magFilter !== undefined ? magFilter : LinearFilter;
	this.minFilter = minFilter !== undefined ? minFilter : LinearMipMapLinearFilter;

	this.anisotropy = anisotropy !== undefined ? anisotropy : 1;

	this.format = format !== undefined ? format : RGBAFormat;
	this.type = type !== undefined ? type : UnsignedByteType;

	this.offset = new Vector2( 0, 0 );
	this.repeat = new Vector2( 1, 1 );

	this.generateMipmaps = true;
	this.premultiplyAlpha = false;
	this.flipY = true;
	this.unpackAlignment = 4;	// valid values: 1, 2, 4, 8 (see http://www.khronos.org/opengles/sdk/docs/man/xhtml/glPixelStorei.xml)


	// Values of encoding !== THREE.LinearEncoding only supported on map, envMap and emissiveMap.
	//
	// Also changing the encoding after already used by a Material will not automatically make the Material
	// update.  You need to explicitly call Material.needsUpdate to trigger it to recompile.
	this.encoding = encoding !== undefined ? encoding : LinearEncoding;

	this.version = 0;
	this.onUpdate = null;

}

Texture.DEFAULT_IMAGE = undefined;
Texture.DEFAULT_MAPPING = UVMapping;

Texture.prototype = {

	constructor: Texture,

	isTexture: true,

	set needsUpdate( value ) {

		if ( value === true ) this.version ++;

	},

	clone: function () {

		return new this.constructor().copy( this );

	},

	copy: function ( source ) {

		this.image = source.image;
		this.mipmaps = source.mipmaps.slice( 0 );

		this.mapping = source.mapping;

		this.wrapS = source.wrapS;
		this.wrapT = source.wrapT;

		this.magFilter = source.magFilter;
		this.minFilter = source.minFilter;

		this.anisotropy = source.anisotropy;

		this.format = source.format;
		this.type = source.type;

		this.offset.copy( source.offset );
		this.repeat.copy( source.repeat );

		this.generateMipmaps = source.generateMipmaps;
		this.premultiplyAlpha = source.premultiplyAlpha;
		this.flipY = source.flipY;
		this.unpackAlignment = source.unpackAlignment;
		this.encoding = source.encoding;

		return this;

	},

	toJSON: function ( meta ) {

		if ( meta.textures[ this.uuid ] !== undefined ) {

			return meta.textures[ this.uuid ];

		}

		function getDataURL( image ) {

			var canvas;

			if ( image.toDataURL !== undefined ) {

				canvas = image;

			} else {

				canvas = document.createElementNS( 'http://www.w3.org/1999/xhtml', 'canvas' );
				canvas.width = image.width;
				canvas.height = image.height;

				canvas.getContext( '2d' ).drawImage( image, 0, 0, image.width, image.height );

			}

			if ( canvas.width > 2048 || canvas.height > 2048 ) {

				return canvas.toDataURL( 'image/jpeg', 0.6 );

			} else {

				return canvas.toDataURL( 'image/png' );

			}

		}

		var output = {
			metadata: {
				version: 4.4,
				type: 'Texture',
				generator: 'Texture.toJSON'
			},

			uuid: this.uuid,
			name: this.name,

			mapping: this.mapping,

			repeat: [ this.repeat.x, this.repeat.y ],
			offset: [ this.offset.x, this.offset.y ],
			wrap: [ this.wrapS, this.wrapT ],

			minFilter: this.minFilter,
			magFilter: this.magFilter,
			anisotropy: this.anisotropy,

			flipY: this.flipY
		};

		if ( this.image !== undefined ) {

			// TODO: Move to THREE.Image

			var image = this.image;

			if ( image.uuid === undefined ) {

				image.uuid = _Math.generateUUID(); // UGH

			}

			if ( meta.images[ image.uuid ] === undefined ) {

				meta.images[ image.uuid ] = {
					uuid: image.uuid,
					url: getDataURL( image )
				};

			}

			output.image = image.uuid;

		}

		meta.textures[ this.uuid ] = output;

		return output;

	},

	dispose: function () {

		this.dispatchEvent( { type: 'dispose' } );

	},

	transformUv: function ( uv ) {

		if ( this.mapping !== UVMapping ) return;

		uv.multiply( this.repeat );
		uv.add( this.offset );

		if ( uv.x < 0 || uv.x > 1 ) {

			switch ( this.wrapS ) {

				case RepeatWrapping:

					uv.x = uv.x - Math.floor( uv.x );
					break;

				case ClampToEdgeWrapping:

					uv.x = uv.x < 0 ? 0 : 1;
					break;

				case MirroredRepeatWrapping:

					if ( Math.abs( Math.floor( uv.x ) % 2 ) === 1 ) {

						uv.x = Math.ceil( uv.x ) - uv.x;

					} else {

						uv.x = uv.x - Math.floor( uv.x );

					}
					break;

			}

		}

		if ( uv.y < 0 || uv.y > 1 ) {

			switch ( this.wrapT ) {

				case RepeatWrapping:

					uv.y = uv.y - Math.floor( uv.y );
					break;

				case ClampToEdgeWrapping:

					uv.y = uv.y < 0 ? 0 : 1;
					break;

				case MirroredRepeatWrapping:

					if ( Math.abs( Math.floor( uv.y ) % 2 ) === 1 ) {

						uv.y = Math.ceil( uv.y ) - uv.y;

					} else {

						uv.y = uv.y - Math.floor( uv.y );

					}
					break;

			}

		}

		if ( this.flipY ) {

			uv.y = 1 - uv.y;

		}

	}

};

Object.assign( Texture.prototype, EventDispatcher.prototype );

/**
 * @author mrdoob / http://mrdoob.com/
 */

function CubeTexture( images, mapping, wrapS, wrapT, magFilter, minFilter, format, type, anisotropy, encoding ) {

	images = images !== undefined ? images : [];
	mapping = mapping !== undefined ? mapping : CubeReflectionMapping;

	Texture.call( this, images, mapping, wrapS, wrapT, magFilter, minFilter, format, type, anisotropy, encoding );

	this.flipY = false;

}

CubeTexture.prototype = Object.create( Texture.prototype );
CubeTexture.prototype.constructor = CubeTexture;

CubeTexture.prototype.isCubeTexture = true;

Object.defineProperty( CubeTexture.prototype, 'images', {

	get: function () {

		return this.image;

	},

	set: function ( value ) {

		this.image = value;

	}

} );

/**
 * @author tschw
 *
 * Uniforms of a program.
 * Those form a tree structure with a special top-level container for the root,
 * which you get by calling 'new WebGLUniforms( gl, program, renderer )'.
 *
 *
 * Properties of inner nodes including the top-level container:
 *
 * .seq - array of nested uniforms
 * .map - nested uniforms by name
 *
 *
 * Methods of all nodes except the top-level container:
 *
 * .setValue( gl, value, [renderer] )
 *
 * 		uploads a uniform value(s)
 *  	the 'renderer' parameter is needed for sampler uniforms
 *
 *
 * Static methods of the top-level container (renderer factorizations):
 *
 * .upload( gl, seq, values, renderer )
 *
 * 		sets uniforms in 'seq' to 'values[id].value'
 *
 * .seqWithValue( seq, values ) : filteredSeq
 *
 * 		filters 'seq' entries with corresponding entry in values
 *
 *
 * Methods of the top-level container (renderer factorizations):
 *
 * .setValue( gl, name, value )
 *
 * 		sets uniform with  name 'name' to 'value'
 *
 * .set( gl, obj, prop )
 *
 * 		sets uniform from object and property with same name than uniform
 *
 * .setOptional( gl, obj, prop )
 *
 * 		like .set for an optional property of the object
 *
 */

var emptyTexture = new Texture();
var emptyCubeTexture = new CubeTexture();

// --- Base for inner nodes (including the root) ---

function UniformContainer() {

	this.seq = [];
	this.map = {};

}

// --- Utilities ---

// Array Caches (provide typed arrays for temporary by size)

var arrayCacheF32 = [];
var arrayCacheI32 = [];

// Flattening for arrays of vectors and matrices

function flatten( array, nBlocks, blockSize ) {

	var firstElem = array[ 0 ];

	if ( firstElem <= 0 || firstElem > 0 ) return array;
	// unoptimized: ! isNaN( firstElem )
	// see http://jacksondunstan.com/articles/983

	var n = nBlocks * blockSize,
		r = arrayCacheF32[ n ];

	if ( r === undefined ) {

		r = new Float32Array( n );
		arrayCacheF32[ n ] = r;

	}

	if ( nBlocks !== 0 ) {

		firstElem.toArray( r, 0 );

		for ( var i = 1, offset = 0; i !== nBlocks; ++ i ) {

			offset += blockSize;
			array[ i ].toArray( r, offset );

		}

	}

	return r;

}

// Texture unit allocation

function allocTexUnits( renderer, n ) {

	var r = arrayCacheI32[ n ];

	if ( r === undefined ) {

		r = new Int32Array( n );
		arrayCacheI32[ n ] = r;

	}

	for ( var i = 0; i !== n; ++ i )
		r[ i ] = renderer.allocTextureUnit();

	return r;

}

// --- Setters ---

// Note: Defining these methods externally, because they come in a bunch
// and this way their names minify.

// Single scalar

function setValue1f( gl, v ) { gl.uniform1f( this.addr, v ); }
function setValue1i( gl, v ) { gl.uniform1i( this.addr, v ); }

// Single float vector (from flat array or THREE.VectorN)

function setValue2fv( gl, v ) {

	if ( v.x === undefined ) gl.uniform2fv( this.addr, v );
	else gl.uniform2f( this.addr, v.x, v.y );

}

function setValue3fv( gl, v ) {

	if ( v.x !== undefined )
		gl.uniform3f( this.addr, v.x, v.y, v.z );
	else if ( v.r !== undefined )
		gl.uniform3f( this.addr, v.r, v.g, v.b );
	else
		gl.uniform3fv( this.addr, v );

}

function setValue4fv( gl, v ) {

	if ( v.x === undefined ) gl.uniform4fv( this.addr, v );
	else gl.uniform4f( this.addr, v.x, v.y, v.z, v.w );

}

// Single matrix (from flat array or MatrixN)

function setValue2fm( gl, v ) {

	gl.uniformMatrix2fv( this.addr, false, v.elements || v );

}

function setValue3fm( gl, v ) {

	gl.uniformMatrix3fv( this.addr, false, v.elements || v );

}

function setValue4fm( gl, v ) {

	gl.uniformMatrix4fv( this.addr, false, v.elements || v );

}

// Single texture (2D / Cube)

function setValueT1( gl, v, renderer ) {

	var unit = renderer.allocTextureUnit();
	gl.uniform1i( this.addr, unit );
	renderer.setTexture2D( v || emptyTexture, unit );

}

function setValueT6( gl, v, renderer ) {

	var unit = renderer.allocTextureUnit();
	gl.uniform1i( this.addr, unit );
	renderer.setTextureCube( v || emptyCubeTexture, unit );

}

// Integer / Boolean vectors or arrays thereof (always flat arrays)

function setValue2iv( gl, v ) { gl.uniform2iv( this.addr, v ); }
function setValue3iv( gl, v ) { gl.uniform3iv( this.addr, v ); }
function setValue4iv( gl, v ) { gl.uniform4iv( this.addr, v ); }

// Helper to pick the right setter for the singular case

function getSingularSetter( type ) {

	switch ( type ) {

		case 0x1406: return setValue1f; // FLOAT
		case 0x8b50: return setValue2fv; // _VEC2
		case 0x8b51: return setValue3fv; // _VEC3
		case 0x8b52: return setValue4fv; // _VEC4

		case 0x8b5a: return setValue2fm; // _MAT2
		case 0x8b5b: return setValue3fm; // _MAT3
		case 0x8b5c: return setValue4fm; // _MAT4

		case 0x8b5e: return setValueT1; // SAMPLER_2D
		case 0x8b60: return setValueT6; // SAMPLER_CUBE

		case 0x1404: case 0x8b56: return setValue1i; // INT, BOOL
		case 0x8b53: case 0x8b57: return setValue2iv; // _VEC2
		case 0x8b54: case 0x8b58: return setValue3iv; // _VEC3
		case 0x8b55: case 0x8b59: return setValue4iv; // _VEC4

	}

}

// Array of scalars

function setValue1fv( gl, v ) { gl.uniform1fv( this.addr, v ); }
function setValue1iv( gl, v ) { gl.uniform1iv( this.addr, v ); }

// Array of vectors (flat or from THREE classes)

function setValueV2a( gl, v ) {

	gl.uniform2fv( this.addr, flatten( v, this.size, 2 ) );

}

function setValueV3a( gl, v ) {

	gl.uniform3fv( this.addr, flatten( v, this.size, 3 ) );

}

function setValueV4a( gl, v ) {

	gl.uniform4fv( this.addr, flatten( v, this.size, 4 ) );

}

// Array of matrices (flat or from THREE clases)

function setValueM2a( gl, v ) {

	gl.uniformMatrix2fv( this.addr, false, flatten( v, this.size, 4 ) );

}

function setValueM3a( gl, v ) {

	gl.uniformMatrix3fv( this.addr, false, flatten( v, this.size, 9 ) );

}

function setValueM4a( gl, v ) {

	gl.uniformMatrix4fv( this.addr, false, flatten( v, this.size, 16 ) );

}

// Array of textures (2D / Cube)

function setValueT1a( gl, v, renderer ) {

	var n = v.length,
		units = allocTexUnits( renderer, n );

	gl.uniform1iv( this.addr, units );

	for ( var i = 0; i !== n; ++ i ) {

		renderer.setTexture2D( v[ i ] || emptyTexture, units[ i ] );

	}

}

function setValueT6a( gl, v, renderer ) {

	var n = v.length,
		units = allocTexUnits( renderer, n );

	gl.uniform1iv( this.addr, units );

	for ( var i = 0; i !== n; ++ i ) {

		renderer.setTextureCube( v[ i ] || emptyCubeTexture, units[ i ] );

	}

}

// Helper to pick the right setter for a pure (bottom-level) array

function getPureArraySetter( type ) {

	switch ( type ) {

		case 0x1406: return setValue1fv; // FLOAT
		case 0x8b50: return setValueV2a; // _VEC2
		case 0x8b51: return setValueV3a; // _VEC3
		case 0x8b52: return setValueV4a; // _VEC4

		case 0x8b5a: return setValueM2a; // _MAT2
		case 0x8b5b: return setValueM3a; // _MAT3
		case 0x8b5c: return setValueM4a; // _MAT4

		case 0x8b5e: return setValueT1a; // SAMPLER_2D
		case 0x8b60: return setValueT6a; // SAMPLER_CUBE

		case 0x1404: case 0x8b56: return setValue1iv; // INT, BOOL
		case 0x8b53: case 0x8b57: return setValue2iv; // _VEC2
		case 0x8b54: case 0x8b58: return setValue3iv; // _VEC3
		case 0x8b55: case 0x8b59: return setValue4iv; // _VEC4

	}

}

// --- Uniform Classes ---

function SingleUniform( id, activeInfo, addr ) {

	this.id = id;
	this.addr = addr;
	this.setValue = getSingularSetter( activeInfo.type );

	// this.path = activeInfo.name; // DEBUG

}

function PureArrayUniform( id, activeInfo, addr ) {

	this.id = id;
	this.addr = addr;
	this.size = activeInfo.size;
	this.setValue = getPureArraySetter( activeInfo.type );

	// this.path = activeInfo.name; // DEBUG

}

function StructuredUniform( id ) {

	this.id = id;

	UniformContainer.call( this ); // mix-in

}

StructuredUniform.prototype.setValue = function( gl, value ) {

	// Note: Don't need an extra 'renderer' parameter, since samplers
	// are not allowed in structured uniforms.

	var seq = this.seq;

	for ( var i = 0, n = seq.length; i !== n; ++ i ) {

		var u = seq[ i ];
		u.setValue( gl, value[ u.id ] );

	}

};

// --- Top-level ---

// Parser - builds up the property tree from the path strings

var RePathPart = /([\w\d_]+)(\])?(\[|\.)?/g;

// extracts
// 	- the identifier (member name or array index)
//  - followed by an optional right bracket (found when array index)
//  - followed by an optional left bracket or dot (type of subscript)
//
// Note: These portions can be read in a non-overlapping fashion and
// allow straightforward parsing of the hierarchy that WebGL encodes
// in the uniform names.

function addUniform( container, uniformObject ) {

	container.seq.push( uniformObject );
	container.map[ uniformObject.id ] = uniformObject;

}

function parseUniform( activeInfo, addr, container ) {

	var path = activeInfo.name,
		pathLength = path.length;

	// reset RegExp object, because of the early exit of a previous run
	RePathPart.lastIndex = 0;

	for (; ;) {

		var match = RePathPart.exec( path ),
			matchEnd = RePathPart.lastIndex,

			id = match[ 1 ],
			idIsIndex = match[ 2 ] === ']',
			subscript = match[ 3 ];

		if ( idIsIndex ) id = id | 0; // convert to integer

		if ( subscript === undefined ||
				subscript === '[' && matchEnd + 2 === pathLength ) {
			// bare name or "pure" bottom-level array "[0]" suffix

			addUniform( container, subscript === undefined ?
					new SingleUniform( id, activeInfo, addr ) :
					new PureArrayUniform( id, activeInfo, addr ) );

			break;

		} else {
			// step into inner node / create it in case it doesn't exist

			var map = container.map,
				next = map[ id ];

			if ( next === undefined ) {

				next = new StructuredUniform( id );
				addUniform( container, next );

			}

			container = next;

		}

	}

}

// Root Container

function WebGLUniforms( gl, program, renderer ) {

	UniformContainer.call( this );

	this.renderer = renderer;

	var n = gl.getProgramParameter( program, gl.ACTIVE_UNIFORMS );

	for ( var i = 0; i < n; ++ i ) {

		var info = gl.getActiveUniform( program, i ),
			path = info.name,
			addr = gl.getUniformLocation( program, path );

		parseUniform( info, addr, this );

	}

}

WebGLUniforms.prototype.setValue = function( gl, name, value ) {

	var u = this.map[ name ];

	if ( u !== undefined ) u.setValue( gl, value, this.renderer );

};

WebGLUniforms.prototype.set = function( gl, object, name ) {

	var u = this.map[ name ];

	if ( u !== undefined ) u.setValue( gl, object[ name ], this.renderer );

};

WebGLUniforms.prototype.setOptional = function( gl, object, name ) {

	var v = object[ name ];

	if ( v !== undefined ) this.setValue( gl, name, v );

};


// Static interface

WebGLUniforms.upload = function( gl, seq, values, renderer ) {

	for ( var i = 0, n = seq.length; i !== n; ++ i ) {

		var u = seq[ i ],
			v = values[ u.id ];

		if ( v.needsUpdate !== false ) {
			// note: always updating when .needsUpdate is undefined

			u.setValue( gl, v.value, renderer );

		}

	}

};

WebGLUniforms.seqWithValue = function( seq, values ) {

	var r = [];

	for ( var i = 0, n = seq.length; i !== n; ++ i ) {

		var u = seq[ i ];
		if ( u.id in values ) r.push( u );

	}

	return r;

};

/**
 * Uniform Utilities
 */

var UniformsUtils = {

	merge: function ( uniforms ) {

		var merged = {};

		for ( var u = 0; u < uniforms.length; u ++ ) {

			var tmp = this.clone( uniforms[ u ] );

			for ( var p in tmp ) {

				merged[ p ] = tmp[ p ];

			}

		}

		return merged;

	},

	clone: function ( uniforms_src ) {

		var uniforms_dst = {};

		for ( var u in uniforms_src ) {

			uniforms_dst[ u ] = {};

			for ( var p in uniforms_src[ u ] ) {

				var parameter_src = uniforms_src[ u ][ p ];

				if ( parameter_src && ( parameter_src.isColor ||
					parameter_src.isMatrix3 || parameter_src.isMatrix4 ||
					parameter_src.isVector2 || parameter_src.isVector3 || parameter_src.isVector4 ||
					parameter_src.isTexture ) ) {

					uniforms_dst[ u ][ p ] = parameter_src.clone();

				} else if ( Array.isArray( parameter_src ) ) {

					uniforms_dst[ u ][ p ] = parameter_src.slice();

				} else {

					uniforms_dst[ u ][ p ] = parameter_src;

				}

			}

		}

		return uniforms_dst;

	}

};

var alphamap_fragment = "#ifdef USE_ALPHAMAP\n\tdiffuseColor.a *= texture2D( alphaMap, vUv ).g;\n#endif\n";

var alphamap_pars_fragment = "#ifdef USE_ALPHAMAP\n\tuniform sampler2D alphaMap;\n#endif\n";

var alphatest_fragment = "#ifdef ALPHATEST\n\tif ( diffuseColor.a < ALPHATEST ) discard;\n#endif\n";

var aomap_fragment = "#ifdef USE_AOMAP\n\tfloat ambientOcclusion = ( texture2D( aoMap, vUv2 ).r - 1.0 ) * aoMapIntensity + 1.0;\n\treflectedLight.indirectDiffuse *= ambientOcclusion;\n\t#if defined( USE_ENVMAP ) && defined( PHYSICAL )\n\t\tfloat dotNV = saturate( dot( geometry.normal, geometry.viewDir ) );\n\t\treflectedLight.indirectSpecular *= computeSpecularOcclusion( dotNV, ambientOcclusion, material.specularRoughness );\n\t#endif\n#endif\n";

var aomap_pars_fragment = "#ifdef USE_AOMAP\n\tuniform sampler2D aoMap;\n\tuniform float aoMapIntensity;\n#endif";

var begin_vertex = "\nvec3 transformed = vec3( position );\n";

var beginnormal_vertex = "\nvec3 objectNormal = vec3( normal );\n";

var bsdfs = "float punctualLightIntensityToIrradianceFactor( const in float lightDistance, const in float cutoffDistance, const in float decayExponent ) {\n\t\tif( decayExponent > 0.0 ) {\n#if defined ( PHYSICALLY_CORRECT_LIGHTS )\n\t\t\tfloat distanceFalloff = 1.0 / max( pow( lightDistance, decayExponent ), 0.01 );\n\t\t\tfloat maxDistanceCutoffFactor = pow2( saturate( 1.0 - pow4( lightDistance / cutoffDistance ) ) );\n\t\t\treturn distanceFalloff * maxDistanceCutoffFactor;\n#else\n\t\t\treturn pow( saturate( -lightDistance / cutoffDistance + 1.0 ), decayExponent );\n#endif\n\t\t}\n\t\treturn 1.0;\n}\nvec3 BRDF_Diffuse_Lambert( const in vec3 diffuseColor ) {\n\treturn RECIPROCAL_PI * diffuseColor;\n}\nvec3 F_Schlick( const in vec3 specularColor, const in float dotLH ) {\n\tfloat fresnel = exp2( ( -5.55473 * dotLH - 6.98316 ) * dotLH );\n\treturn ( 1.0 - specularColor ) * fresnel + specularColor;\n}\nfloat G_GGX_Smith( const in float alpha, const in float dotNL, const in float dotNV ) {\n\tfloat a2 = pow2( alpha );\n\tfloat gl = dotNL + sqrt( a2 + ( 1.0 - a2 ) * pow2( dotNL ) );\n\tfloat gv = dotNV + sqrt( a2 + ( 1.0 - a2 ) * pow2( dotNV ) );\n\treturn 1.0 / ( gl * gv );\n}\nfloat G_GGX_SmithCorrelated( const in float alpha, const in float dotNL, const in float dotNV ) {\n\tfloat a2 = pow2( alpha );\n\tfloat gv = dotNL * sqrt( a2 + ( 1.0 - a2 ) * pow2( dotNV ) );\n\tfloat gl = dotNV * sqrt( a2 + ( 1.0 - a2 ) * pow2( dotNL ) );\n\treturn 0.5 / max( gv + gl, EPSILON );\n}\nfloat D_GGX( const in float alpha, const in float dotNH ) {\n\tfloat a2 = pow2( alpha );\n\tfloat denom = pow2( dotNH ) * ( a2 - 1.0 ) + 1.0;\n\treturn RECIPROCAL_PI * a2 / pow2( denom );\n}\nvec3 BRDF_Specular_GGX( const in IncidentLight incidentLight, const in GeometricContext geometry, const in vec3 specularColor, const in float roughness ) {\n\tfloat alpha = pow2( roughness );\n\tvec3 halfDir = normalize( incidentLight.direction + geometry.viewDir );\n\tfloat dotNL = saturate( dot( geometry.normal, incidentLight.direction ) );\n\tfloat dotNV = saturate( dot( geometry.normal, geometry.viewDir ) );\n\tfloat dotNH = saturate( dot( geometry.normal, halfDir ) );\n\tfloat dotLH = saturate( dot( incidentLight.direction, halfDir ) );\n\tvec3 F = F_Schlick( specularColor, dotLH );\n\tfloat G = G_GGX_SmithCorrelated( alpha, dotNL, dotNV );\n\tfloat D = D_GGX( alpha, dotNH );\n\treturn F * ( G * D );\n}\nvec2 ltcTextureCoords( const in GeometricContext geometry, const in float roughness ) {\n\tconst float LUT_SIZE  = 64.0;\n\tconst float LUT_SCALE = (LUT_SIZE - 1.0)/LUT_SIZE;\n\tconst float LUT_BIAS  = 0.5/LUT_SIZE;\n\tvec3 N = geometry.normal;\n\tvec3 V = geometry.viewDir;\n\tvec3 P = geometry.position;\n\tfloat theta = acos( dot( N, V ) );\n\tvec2 uv = vec2(\n\t\tsqrt( saturate( roughness ) ),\n\t\tsaturate( theta / ( 0.5 * PI ) ) );\n\tuv = uv * LUT_SCALE + LUT_BIAS;\n\treturn uv;\n}\nvoid clipQuadToHorizon( inout vec3 L[5], out int n ) {\n\tint config = 0;\n\tif ( L[0].z > 0.0 ) config += 1;\n\tif ( L[1].z > 0.0 ) config += 2;\n\tif ( L[2].z > 0.0 ) config += 4;\n\tif ( L[3].z > 0.0 ) config += 8;\n\tn = 0;\n\tif ( config == 0 ) {\n\t} else if ( config == 1 ) {\n\t\tn = 3;\n\t\tL[1] = -L[1].z * L[0] + L[0].z * L[1];\n\t\tL[2] = -L[3].z * L[0] + L[0].z * L[3];\n\t} else if ( config == 2 ) {\n\t\tn = 3;\n\t\tL[0] = -L[0].z * L[1] + L[1].z * L[0];\n\t\tL[2] = -L[2].z * L[1] + L[1].z * L[2];\n\t} else if ( config == 3 ) {\n\t\tn = 4;\n\t\tL[2] = -L[2].z * L[1] + L[1].z * L[2];\n\t\tL[3] = -L[3].z * L[0] + L[0].z * L[3];\n\t} else if ( config == 4 ) {\n\t\tn = 3;\n\t\tL[0] = -L[3].z * L[2] + L[2].z * L[3];\n\t\tL[1] = -L[1].z * L[2] + L[2].z * L[1];\n\t} else if ( config == 5 ) {\n\t\tn = 0;\n\t} else if ( config == 6 ) {\n\t\tn = 4;\n\t\tL[0] = -L[0].z * L[1] + L[1].z * L[0];\n\t\tL[3] = -L[3].z * L[2] + L[2].z * L[3];\n\t} else if ( config == 7 ) {\n\t\tn = 5;\n\t\tL[4] = -L[3].z * L[0] + L[0].z * L[3];\n\t\tL[3] = -L[3].z * L[2] + L[2].z * L[3];\n\t} else if ( config == 8 ) {\n\t\tn = 3;\n\t\tL[0] = -L[0].z * L[3] + L[3].z * L[0];\n\t\tL[1] = -L[2].z * L[3] + L[3].z * L[2];\n\t\tL[2] =  L[3];\n\t} else if ( config == 9 ) {\n\t\tn = 4;\n\t\tL[1] = -L[1].z * L[0] + L[0].z * L[1];\n\t\tL[2] = -L[2].z * L[3] + L[3].z * L[2];\n\t} else if ( config == 10 ) {\n\t\tn = 0;\n\t} else if ( config == 11 ) {\n\t\tn = 5;\n\t\tL[4] = L[3];\n\t\tL[3] = -L[2].z * L[3] + L[3].z * L[2];\n\t\tL[2] = -L[2].z * L[1] + L[1].z * L[2];\n\t} else if ( config == 12 ) {\n\t\tn = 4;\n\t\tL[1] = -L[1].z * L[2] + L[2].z * L[1];\n\t\tL[0] = -L[0].z * L[3] + L[3].z * L[0];\n\t} else if ( config == 13 ) {\n\t\tn = 5;\n\t\tL[4] = L[3];\n\t\tL[3] = L[2];\n\t\tL[2] = -L[1].z * L[2] + L[2].z * L[1];\n\t\tL[1] = -L[1].z * L[0] + L[0].z * L[1];\n\t} else if ( config == 14 ) {\n\t\tn = 5;\n\t\tL[4] = -L[0].z * L[3] + L[3].z * L[0];\n\t\tL[0] = -L[0].z * L[1] + L[1].z * L[0];\n\t} else if ( config == 15 ) {\n\t\tn = 4;\n\t}\n\tif ( n == 3 )\n\t\tL[3] = L[0];\n\tif ( n == 4 )\n\t\tL[4] = L[0];\n}\nfloat integrateLtcBrdfOverRectEdge( vec3 v1, vec3 v2 ) {\n\tfloat cosTheta = dot( v1, v2 );\n\tfloat theta = acos( cosTheta );\n\tfloat res = cross( v1, v2 ).z * ( ( theta > 0.001 ) ? theta / sin( theta ) : 1.0 );\n\treturn res;\n}\nvoid initRectPoints( const in vec3 pos, const in vec3 halfWidth, const in vec3 halfHeight, out vec3 rectPoints[4] ) {\n\trectPoints[0] = pos - halfWidth - halfHeight;\n\trectPoints[1] = pos + halfWidth - halfHeight;\n\trectPoints[2] = pos + halfWidth + halfHeight;\n\trectPoints[3] = pos - halfWidth + halfHeight;\n}\nvec3 integrateLtcBrdfOverRect( const in GeometricContext geometry, const in mat3 brdfMat, const in vec3 rectPoints[4] ) {\n\tvec3 N = geometry.normal;\n\tvec3 V = geometry.viewDir;\n\tvec3 P = geometry.position;\n\tvec3 T1, T2;\n\tT1 = normalize(V - N * dot( V, N ));\n\tT2 = - cross( N, T1 );\n\tmat3 brdfWrtSurface = brdfMat * transpose( mat3( T1, T2, N ) );\n\tvec3 clippedRect[5];\n\tclippedRect[0] = brdfWrtSurface * ( rectPoints[0] - P );\n\tclippedRect[1] = brdfWrtSurface * ( rectPoints[1] - P );\n\tclippedRect[2] = brdfWrtSurface * ( rectPoints[2] - P );\n\tclippedRect[3] = brdfWrtSurface * ( rectPoints[3] - P );\n\tint n;\n\tclipQuadToHorizon(clippedRect, n);\n\tif ( n == 0 )\n\t\treturn vec3( 0, 0, 0 );\n\tclippedRect[0] = normalize( clippedRect[0] );\n\tclippedRect[1] = normalize( clippedRect[1] );\n\tclippedRect[2] = normalize( clippedRect[2] );\n\tclippedRect[3] = normalize( clippedRect[3] );\n\tclippedRect[4] = normalize( clippedRect[4] );\n\tfloat sum = 0.0;\n\tsum += integrateLtcBrdfOverRectEdge( clippedRect[0], clippedRect[1] );\n\tsum += integrateLtcBrdfOverRectEdge( clippedRect[1], clippedRect[2] );\n\tsum += integrateLtcBrdfOverRectEdge( clippedRect[2], clippedRect[3] );\n\tif (n >= 4)\n\t\tsum += integrateLtcBrdfOverRectEdge( clippedRect[3], clippedRect[4] );\n\tif (n == 5)\n\t\tsum += integrateLtcBrdfOverRectEdge( clippedRect[4], clippedRect[0] );\n\tsum = max( 0.0, sum );\n\tvec3 Lo_i = vec3( sum, sum, sum );\n\treturn Lo_i;\n}\nvec3 Rect_Area_Light_Specular_Reflectance(\n\t\tconst in GeometricContext geometry,\n\t\tconst in vec3 lightPos, const in vec3 lightHalfWidth, const in vec3 lightHalfHeight,\n\t\tconst in float roughness,\n\t\tconst in sampler2D ltcMat, const in sampler2D ltcMag ) {\n\tvec3 rectPoints[4];\n\tinitRectPoints( lightPos, lightHalfWidth, lightHalfHeight, rectPoints );\n\tvec2 uv = ltcTextureCoords( geometry, roughness );\n\tvec4 brdfLtcApproxParams, t;\n\tbrdfLtcApproxParams = texture2D( ltcMat, uv );\n\tt = texture2D( ltcMat, uv );\n\tfloat brdfLtcScalar = texture2D( ltcMag, uv ).a;\n\tmat3 brdfLtcApproxMat = mat3(\n\t\tvec3(   1,   0, t.y ),\n\t\tvec3(   0, t.z,   0 ),\n\t\tvec3( t.w,   0, t.x )\n\t);\n\tvec3 specularReflectance = integrateLtcBrdfOverRect( geometry, brdfLtcApproxMat, rectPoints );\n\tspecularReflectance *= brdfLtcScalar;\n\treturn specularReflectance;\n}\nvec3 Rect_Area_Light_Diffuse_Reflectance(\n\t\tconst in GeometricContext geometry,\n\t\tconst in vec3 lightPos, const in vec3 lightHalfWidth, const in vec3 lightHalfHeight ) {\n\tvec3 rectPoints[4];\n\tinitRectPoints( lightPos, lightHalfWidth, lightHalfHeight, rectPoints );\n\tmat3 diffuseBrdfMat = mat3(1);\n\tvec3 diffuseReflectance = integrateLtcBrdfOverRect( geometry, diffuseBrdfMat, rectPoints );\n\treturn diffuseReflectance;\n}\nvec3 BRDF_Specular_GGX_Environment( const in GeometricContext geometry, const in vec3 specularColor, const in float roughness ) {\n\tfloat dotNV = saturate( dot( geometry.normal, geometry.viewDir ) );\n\tconst vec4 c0 = vec4( - 1, - 0.0275, - 0.572, 0.022 );\n\tconst vec4 c1 = vec4( 1, 0.0425, 1.04, - 0.04 );\n\tvec4 r = roughness * c0 + c1;\n\tfloat a004 = min( r.x * r.x, exp2( - 9.28 * dotNV ) ) * r.x + r.y;\n\tvec2 AB = vec2( -1.04, 1.04 ) * a004 + r.zw;\n\treturn specularColor * AB.x + AB.y;\n}\nfloat G_BlinnPhong_Implicit( ) {\n\treturn 0.25;\n}\nfloat D_BlinnPhong( const in float shininess, const in float dotNH ) {\n\treturn RECIPROCAL_PI * ( shininess * 0.5 + 1.0 ) * pow( dotNH, shininess );\n}\nvec3 BRDF_Specular_BlinnPhong( const in IncidentLight incidentLight, const in GeometricContext geometry, const in vec3 specularColor, const in float shininess ) {\n\tvec3 halfDir = normalize( incidentLight.direction + geometry.viewDir );\n\tfloat dotNH = saturate( dot( geometry.normal, halfDir ) );\n\tfloat dotLH = saturate( dot( incidentLight.direction, halfDir ) );\n\tvec3 F = F_Schlick( specularColor, dotLH );\n\tfloat G = G_BlinnPhong_Implicit( );\n\tfloat D = D_BlinnPhong( shininess, dotNH );\n\treturn F * ( G * D );\n}\nfloat GGXRoughnessToBlinnExponent( const in float ggxRoughness ) {\n\treturn ( 2.0 / pow2( ggxRoughness + 0.0001 ) - 2.0 );\n}\nfloat BlinnExponentToGGXRoughness( const in float blinnExponent ) {\n\treturn sqrt( 2.0 / ( blinnExponent + 2.0 ) );\n}\n";

var bumpmap_pars_fragment = "#ifdef USE_BUMPMAP\n\tuniform sampler2D bumpMap;\n\tuniform float bumpScale;\n\tvec2 dHdxy_fwd() {\n\t\tvec2 dSTdx = dFdx( vUv );\n\t\tvec2 dSTdy = dFdy( vUv );\n\t\tfloat Hll = bumpScale * texture2D( bumpMap, vUv ).x;\n\t\tfloat dBx = bumpScale * texture2D( bumpMap, vUv + dSTdx ).x - Hll;\n\t\tfloat dBy = bumpScale * texture2D( bumpMap, vUv + dSTdy ).x - Hll;\n\t\treturn vec2( dBx, dBy );\n\t}\n\tvec3 perturbNormalArb( vec3 surf_pos, vec3 surf_norm, vec2 dHdxy ) {\n\t\tvec3 vSigmaX = dFdx( surf_pos );\n\t\tvec3 vSigmaY = dFdy( surf_pos );\n\t\tvec3 vN = surf_norm;\n\t\tvec3 R1 = cross( vSigmaY, vN );\n\t\tvec3 R2 = cross( vN, vSigmaX );\n\t\tfloat fDet = dot( vSigmaX, R1 );\n\t\tvec3 vGrad = sign( fDet ) * ( dHdxy.x * R1 + dHdxy.y * R2 );\n\t\treturn normalize( abs( fDet ) * surf_norm - vGrad );\n\t}\n#endif\n";

var clipping_planes_fragment = "#if NUM_CLIPPING_PLANES > 0\n\tfor ( int i = 0; i < UNION_CLIPPING_PLANES; ++ i ) {\n\t\tvec4 plane = clippingPlanes[ i ];\n\t\tif ( dot( vViewPosition, plane.xyz ) > plane.w ) discard;\n\t}\n\t\t\n\t#if UNION_CLIPPING_PLANES < NUM_CLIPPING_PLANES\n\t\tbool clipped = true;\n\t\tfor ( int i = UNION_CLIPPING_PLANES; i < NUM_CLIPPING_PLANES; ++ i ) {\n\t\t\tvec4 plane = clippingPlanes[ i ];\n\t\t\tclipped = ( dot( vViewPosition, plane.xyz ) > plane.w ) && clipped;\n\t\t}\n\t\tif ( clipped ) discard;\n\t\n\t#endif\n#endif\n";

var clipping_planes_pars_fragment = "#if NUM_CLIPPING_PLANES > 0\n\t#if ! defined( PHYSICAL ) && ! defined( PHONG )\n\t\tvarying vec3 vViewPosition;\n\t#endif\n\tuniform vec4 clippingPlanes[ NUM_CLIPPING_PLANES ];\n#endif\n";

var clipping_planes_pars_vertex = "#if NUM_CLIPPING_PLANES > 0 && ! defined( PHYSICAL ) && ! defined( PHONG )\n\tvarying vec3 vViewPosition;\n#endif\n";

var clipping_planes_vertex = "#if NUM_CLIPPING_PLANES > 0 && ! defined( PHYSICAL ) && ! defined( PHONG )\n\tvViewPosition = - mvPosition.xyz;\n#endif\n";

var color_fragment = "#ifdef USE_COLOR\n\tdiffuseColor.rgb *= vColor;\n#endif";

var color_pars_fragment = "#ifdef USE_COLOR\n\tvarying vec3 vColor;\n#endif\n";

var color_pars_vertex = "#ifdef USE_COLOR\n\tvarying vec3 vColor;\n#endif";

var color_vertex = "#ifdef USE_COLOR\n\tvColor.xyz = color.xyz;\n#endif";

var common = "#define PI 3.14159265359\n#define PI2 6.28318530718\n#define PI_HALF 1.5707963267949\n#define RECIPROCAL_PI 0.31830988618\n#define RECIPROCAL_PI2 0.15915494\n#define LOG2 1.442695\n#define EPSILON 1e-6\n#define saturate(a) clamp( a, 0.0, 1.0 )\n#define whiteCompliment(a) ( 1.0 - saturate( a ) )\nfloat pow2( const in float x ) { return x*x; }\nfloat pow3( const in float x ) { return x*x*x; }\nfloat pow4( const in float x ) { float x2 = x*x; return x2*x2; }\nfloat average( const in vec3 color ) { return dot( color, vec3( 0.3333 ) ); }\nhighp float rand( const in vec2 uv ) {\n\tconst highp float a = 12.9898, b = 78.233, c = 43758.5453;\n\thighp float dt = dot( uv.xy, vec2( a,b ) ), sn = mod( dt, PI );\n\treturn fract(sin(sn) * c);\n}\nstruct IncidentLight {\n\tvec3 color;\n\tvec3 direction;\n\tbool visible;\n};\nstruct ReflectedLight {\n\tvec3 directDiffuse;\n\tvec3 directSpecular;\n\tvec3 indirectDiffuse;\n\tvec3 indirectSpecular;\n};\nstruct GeometricContext {\n\tvec3 position;\n\tvec3 normal;\n\tvec3 viewDir;\n};\nvec3 transformDirection( in vec3 dir, in mat4 matrix ) {\n\treturn normalize( ( matrix * vec4( dir, 0.0 ) ).xyz );\n}\nvec3 inverseTransformDirection( in vec3 dir, in mat4 matrix ) {\n\treturn normalize( ( vec4( dir, 0.0 ) * matrix ).xyz );\n}\nvec3 projectOnPlane(in vec3 point, in vec3 pointOnPlane, in vec3 planeNormal ) {\n\tfloat distance = dot( planeNormal, point - pointOnPlane );\n\treturn - distance * planeNormal + point;\n}\nfloat sideOfPlane( in vec3 point, in vec3 pointOnPlane, in vec3 planeNormal ) {\n\treturn sign( dot( point - pointOnPlane, planeNormal ) );\n}\nvec3 linePlaneIntersect( in vec3 pointOnLine, in vec3 lineDirection, in vec3 pointOnPlane, in vec3 planeNormal ) {\n\treturn lineDirection * ( dot( planeNormal, pointOnPlane - pointOnLine ) / dot( planeNormal, lineDirection ) ) + pointOnLine;\n}\nmat3 transpose( const in mat3 v ) {\n\tmat3 tmp;\n\ttmp[0] = vec3(v[0].x, v[1].x, v[2].x);\n\ttmp[1] = vec3(v[0].y, v[1].y, v[2].y);\n\ttmp[2] = vec3(v[0].z, v[1].z, v[2].z);\n\treturn tmp;\n}\n";

var cube_uv_reflection_fragment = "#ifdef ENVMAP_TYPE_CUBE_UV\n#define cubeUV_textureSize (1024.0)\nint getFaceFromDirection(vec3 direction) {\n\tvec3 absDirection = abs(direction);\n\tint face = -1;\n\tif( absDirection.x > absDirection.z ) {\n\t\tif(absDirection.x > absDirection.y )\n\t\t\tface = direction.x > 0.0 ? 0 : 3;\n\t\telse\n\t\t\tface = direction.y > 0.0 ? 1 : 4;\n\t}\n\telse {\n\t\tif(absDirection.z > absDirection.y )\n\t\t\tface = direction.z > 0.0 ? 2 : 5;\n\t\telse\n\t\t\tface = direction.y > 0.0 ? 1 : 4;\n\t}\n\treturn face;\n}\n#define cubeUV_maxLods1  (log2(cubeUV_textureSize*0.25) - 1.0)\n#define cubeUV_rangeClamp (exp2((6.0 - 1.0) * 2.0))\nvec2 MipLevelInfo( vec3 vec, float roughnessLevel, float roughness ) {\n\tfloat scale = exp2(cubeUV_maxLods1 - roughnessLevel);\n\tfloat dxRoughness = dFdx(roughness);\n\tfloat dyRoughness = dFdy(roughness);\n\tvec3 dx = dFdx( vec * scale * dxRoughness );\n\tvec3 dy = dFdy( vec * scale * dyRoughness );\n\tfloat d = max( dot( dx, dx ), dot( dy, dy ) );\n\td = clamp(d, 1.0, cubeUV_rangeClamp);\n\tfloat mipLevel = 0.5 * log2(d);\n\treturn vec2(floor(mipLevel), fract(mipLevel));\n}\n#define cubeUV_maxLods2 (log2(cubeUV_textureSize*0.25) - 2.0)\n#define cubeUV_rcpTextureSize (1.0 / cubeUV_textureSize)\nvec2 getCubeUV(vec3 direction, float roughnessLevel, float mipLevel) {\n\tmipLevel = roughnessLevel > cubeUV_maxLods2 - 3.0 ? 0.0 : mipLevel;\n\tfloat a = 16.0 * cubeUV_rcpTextureSize;\n\tvec2 exp2_packed = exp2( vec2( roughnessLevel, mipLevel ) );\n\tvec2 rcp_exp2_packed = vec2( 1.0 ) / exp2_packed;\n\tfloat powScale = exp2_packed.x * exp2_packed.y;\n\tfloat scale = rcp_exp2_packed.x * rcp_exp2_packed.y * 0.25;\n\tfloat mipOffset = 0.75*(1.0 - rcp_exp2_packed.y) * rcp_exp2_packed.x;\n\tbool bRes = mipLevel == 0.0;\n\tscale =  bRes && (scale < a) ? a : scale;\n\tvec3 r;\n\tvec2 offset;\n\tint face = getFaceFromDirection(direction);\n\tfloat rcpPowScale = 1.0 / powScale;\n\tif( face == 0) {\n\t\tr = vec3(direction.x, -direction.z, direction.y);\n\t\toffset = vec2(0.0+mipOffset,0.75 * rcpPowScale);\n\t\toffset.y = bRes && (offset.y < 2.0*a) ? a : offset.y;\n\t}\n\telse if( face == 1) {\n\t\tr = vec3(direction.y, direction.x, direction.z);\n\t\toffset = vec2(scale+mipOffset, 0.75 * rcpPowScale);\n\t\toffset.y = bRes && (offset.y < 2.0*a) ? a : offset.y;\n\t}\n\telse if( face == 2) {\n\t\tr = vec3(direction.z, direction.x, direction.y);\n\t\toffset = vec2(2.0*scale+mipOffset, 0.75 * rcpPowScale);\n\t\toffset.y = bRes && (offset.y < 2.0*a) ? a : offset.y;\n\t}\n\telse if( face == 3) {\n\t\tr = vec3(direction.x, direction.z, direction.y);\n\t\toffset = vec2(0.0+mipOffset,0.5 * rcpPowScale);\n\t\toffset.y = bRes && (offset.y < 2.0*a) ? 0.0 : offset.y;\n\t}\n\telse if( face == 4) {\n\t\tr = vec3(direction.y, direction.x, -direction.z);\n\t\toffset = vec2(scale+mipOffset, 0.5 * rcpPowScale);\n\t\toffset.y = bRes && (offset.y < 2.0*a) ? 0.0 : offset.y;\n\t}\n\telse {\n\t\tr = vec3(direction.z, -direction.x, direction.y);\n\t\toffset = vec2(2.0*scale+mipOffset, 0.5 * rcpPowScale);\n\t\toffset.y = bRes && (offset.y < 2.0*a) ? 0.0 : offset.y;\n\t}\n\tr = normalize(r);\n\tfloat texelOffset = 0.5 * cubeUV_rcpTextureSize;\n\tvec2 s = ( r.yz / abs( r.x ) + vec2( 1.0 ) ) * 0.5;\n\tvec2 base = offset + vec2( texelOffset );\n\treturn base + s * ( scale - 2.0 * texelOffset );\n}\n#define cubeUV_maxLods3 (log2(cubeUV_textureSize*0.25) - 3.0)\nvec4 textureCubeUV(vec3 reflectedDirection, float roughness ) {\n\tfloat roughnessVal = roughness* cubeUV_maxLods3;\n\tfloat r1 = floor(roughnessVal);\n\tfloat r2 = r1 + 1.0;\n\tfloat t = fract(roughnessVal);\n\tvec2 mipInfo = MipLevelInfo(reflectedDirection, r1, roughness);\n\tfloat s = mipInfo.y;\n\tfloat level0 = mipInfo.x;\n\tfloat level1 = level0 + 1.0;\n\tlevel1 = level1 > 5.0 ? 5.0 : level1;\n\tlevel0 += min( floor( s + 0.5 ), 5.0 );\n\tvec2 uv_10 = getCubeUV(reflectedDirection, r1, level0);\n\tvec4 color10 = envMapTexelToLinear(texture2D(envMap, uv_10));\n\tvec2 uv_20 = getCubeUV(reflectedDirection, r2, level0);\n\tvec4 color20 = envMapTexelToLinear(texture2D(envMap, uv_20));\n\tvec4 result = mix(color10, color20, t);\n\treturn vec4(result.rgb, 1.0);\n}\n#endif\n";

var defaultnormal_vertex = "#ifdef FLIP_SIDED\n\tobjectNormal = -objectNormal;\n#endif\nvec3 transformedNormal = normalMatrix * objectNormal;\n";

var displacementmap_pars_vertex = "#ifdef USE_DISPLACEMENTMAP\n\tuniform sampler2D displacementMap;\n\tuniform float displacementScale;\n\tuniform float displacementBias;\n#endif\n";

var displacementmap_vertex = "#ifdef USE_DISPLACEMENTMAP\n\ttransformed += normal * ( texture2D( displacementMap, uv ).x * displacementScale + displacementBias );\n#endif\n";

var emissivemap_fragment = "#ifdef USE_EMISSIVEMAP\n\tvec4 emissiveColor = texture2D( emissiveMap, vUv );\n\temissiveColor.rgb = emissiveMapTexelToLinear( emissiveColor ).rgb;\n\ttotalEmissiveRadiance *= emissiveColor.rgb;\n#endif\n";

var emissivemap_pars_fragment = "#ifdef USE_EMISSIVEMAP\n\tuniform sampler2D emissiveMap;\n#endif\n";

var encodings_fragment = "  gl_FragColor = linearToOutputTexel( gl_FragColor );\n";

var encodings_pars_fragment = "\nvec4 LinearToLinear( in vec4 value ) {\n\treturn value;\n}\nvec4 GammaToLinear( in vec4 value, in float gammaFactor ) {\n\treturn vec4( pow( value.xyz, vec3( gammaFactor ) ), value.w );\n}\nvec4 LinearToGamma( in vec4 value, in float gammaFactor ) {\n\treturn vec4( pow( value.xyz, vec3( 1.0 / gammaFactor ) ), value.w );\n}\nvec4 sRGBToLinear( in vec4 value ) {\n\treturn vec4( mix( pow( value.rgb * 0.9478672986 + vec3( 0.0521327014 ), vec3( 2.4 ) ), value.rgb * 0.0773993808, vec3( lessThanEqual( value.rgb, vec3( 0.04045 ) ) ) ), value.w );\n}\nvec4 LinearTosRGB( in vec4 value ) {\n\treturn vec4( mix( pow( value.rgb, vec3( 0.41666 ) ) * 1.055 - vec3( 0.055 ), value.rgb * 12.92, vec3( lessThanEqual( value.rgb, vec3( 0.0031308 ) ) ) ), value.w );\n}\nvec4 RGBEToLinear( in vec4 value ) {\n\treturn vec4( value.rgb * exp2( value.a * 255.0 - 128.0 ), 1.0 );\n}\nvec4 LinearToRGBE( in vec4 value ) {\n\tfloat maxComponent = max( max( value.r, value.g ), value.b );\n\tfloat fExp = clamp( ceil( log2( maxComponent ) ), -128.0, 127.0 );\n\treturn vec4( value.rgb / exp2( fExp ), ( fExp + 128.0 ) / 255.0 );\n}\nvec4 RGBMToLinear( in vec4 value, in float maxRange ) {\n\treturn vec4( value.xyz * value.w * maxRange, 1.0 );\n}\nvec4 LinearToRGBM( in vec4 value, in float maxRange ) {\n\tfloat maxRGB = max( value.x, max( value.g, value.b ) );\n\tfloat M      = clamp( maxRGB / maxRange, 0.0, 1.0 );\n\tM            = ceil( M * 255.0 ) / 255.0;\n\treturn vec4( value.rgb / ( M * maxRange ), M );\n}\nvec4 RGBDToLinear( in vec4 value, in float maxRange ) {\n\treturn vec4( value.rgb * ( ( maxRange / 255.0 ) / value.a ), 1.0 );\n}\nvec4 LinearToRGBD( in vec4 value, in float maxRange ) {\n\tfloat maxRGB = max( value.x, max( value.g, value.b ) );\n\tfloat D      = max( maxRange / maxRGB, 1.0 );\n\tD            = min( floor( D ) / 255.0, 1.0 );\n\treturn vec4( value.rgb * ( D * ( 255.0 / maxRange ) ), D );\n}\nconst mat3 cLogLuvM = mat3( 0.2209, 0.3390, 0.4184, 0.1138, 0.6780, 0.7319, 0.0102, 0.1130, 0.2969 );\nvec4 LinearToLogLuv( in vec4 value )  {\n\tvec3 Xp_Y_XYZp = value.rgb * cLogLuvM;\n\tXp_Y_XYZp = max(Xp_Y_XYZp, vec3(1e-6, 1e-6, 1e-6));\n\tvec4 vResult;\n\tvResult.xy = Xp_Y_XYZp.xy / Xp_Y_XYZp.z;\n\tfloat Le = 2.0 * log2(Xp_Y_XYZp.y) + 127.0;\n\tvResult.w = fract(Le);\n\tvResult.z = (Le - (floor(vResult.w*255.0))/255.0)/255.0;\n\treturn vResult;\n}\nconst mat3 cLogLuvInverseM = mat3( 6.0014, -2.7008, -1.7996, -1.3320, 3.1029, -5.7721, 0.3008, -1.0882, 5.6268 );\nvec4 LogLuvToLinear( in vec4 value ) {\n\tfloat Le = value.z * 255.0 + value.w;\n\tvec3 Xp_Y_XYZp;\n\tXp_Y_XYZp.y = exp2((Le - 127.0) / 2.0);\n\tXp_Y_XYZp.z = Xp_Y_XYZp.y / value.y;\n\tXp_Y_XYZp.x = value.x * Xp_Y_XYZp.z;\n\tvec3 vRGB = Xp_Y_XYZp.rgb * cLogLuvInverseM;\n\treturn vec4( max(vRGB, 0.0), 1.0 );\n}\n";

var envmap_fragment = "#ifdef USE_ENVMAP\n\t#if defined( USE_BUMPMAP ) || defined( USE_NORMALMAP ) || defined( PHONG )\n\t\tvec3 cameraToVertex = normalize( vWorldPosition - cameraPosition );\n\t\tvec3 worldNormal = inverseTransformDirection( normal, viewMatrix );\n\t\t#ifdef ENVMAP_MODE_REFLECTION\n\t\t\tvec3 reflectVec = reflect( cameraToVertex, worldNormal );\n\t\t#else\n\t\t\tvec3 reflectVec = refract( cameraToVertex, worldNormal, refractionRatio );\n\t\t#endif\n\t#else\n\t\tvec3 reflectVec = vReflect;\n\t#endif\n\t#ifdef ENVMAP_TYPE_CUBE\n\t\tvec4 envColor = textureCube( envMap, flipNormal * vec3( flipEnvMap * reflectVec.x, reflectVec.yz ) );\n\t#elif defined( ENVMAP_TYPE_EQUIREC )\n\t\tvec2 sampleUV;\n\t\tsampleUV.y = saturate( flipNormal * reflectVec.y * 0.5 + 0.5 );\n\t\tsampleUV.x = atan( flipNormal * reflectVec.z, flipNormal * reflectVec.x ) * RECIPROCAL_PI2 + 0.5;\n\t\tvec4 envColor = texture2D( envMap, sampleUV );\n\t#elif defined( ENVMAP_TYPE_SPHERE )\n\t\tvec3 reflectView = flipNormal * normalize( ( viewMatrix * vec4( reflectVec, 0.0 ) ).xyz + vec3( 0.0, 0.0, 1.0 ) );\n\t\tvec4 envColor = texture2D( envMap, reflectView.xy * 0.5 + 0.5 );\n\t#else\n\t\tvec4 envColor = vec4( 0.0 );\n\t#endif\n\tenvColor = envMapTexelToLinear( envColor );\n\t#ifdef ENVMAP_BLENDING_MULTIPLY\n\t\toutgoingLight = mix( outgoingLight, outgoingLight * envColor.xyz, specularStrength * reflectivity );\n\t#elif defined( ENVMAP_BLENDING_MIX )\n\t\toutgoingLight = mix( outgoingLight, envColor.xyz, specularStrength * reflectivity );\n\t#elif defined( ENVMAP_BLENDING_ADD )\n\t\toutgoingLight += envColor.xyz * specularStrength * reflectivity;\n\t#endif\n#endif\n";

var envmap_pars_fragment = "#if defined( USE_ENVMAP ) || defined( PHYSICAL )\n\tuniform float reflectivity;\n\tuniform float envMapIntensity;\n#endif\n#ifdef USE_ENVMAP\n\t#if ! defined( PHYSICAL ) && ( defined( USE_BUMPMAP ) || defined( USE_NORMALMAP ) || defined( PHONG ) )\n\t\tvarying vec3 vWorldPosition;\n\t#endif\n\t#ifdef ENVMAP_TYPE_CUBE\n\t\tuniform samplerCube envMap;\n\t#else\n\t\tuniform sampler2D envMap;\n\t#endif\n\tuniform float flipEnvMap;\n\t#if defined( USE_BUMPMAP ) || defined( USE_NORMALMAP ) || defined( PHONG ) || defined( PHYSICAL )\n\t\tuniform float refractionRatio;\n\t#else\n\t\tvarying vec3 vReflect;\n\t#endif\n#endif\n";

var envmap_pars_vertex = "#ifdef USE_ENVMAP\n\t#if defined( USE_BUMPMAP ) || defined( USE_NORMALMAP ) || defined( PHONG )\n\t\tvarying vec3 vWorldPosition;\n\t#else\n\t\tvarying vec3 vReflect;\n\t\tuniform float refractionRatio;\n\t#endif\n#endif\n";

var envmap_vertex = "#ifdef USE_ENVMAP\n\t#if defined( USE_BUMPMAP ) || defined( USE_NORMALMAP ) || defined( PHONG )\n\t\tvWorldPosition = worldPosition.xyz;\n\t#else\n\t\tvec3 cameraToVertex = normalize( worldPosition.xyz - cameraPosition );\n\t\tvec3 worldNormal = inverseTransformDirection( transformedNormal, viewMatrix );\n\t\t#ifdef ENVMAP_MODE_REFLECTION\n\t\t\tvReflect = reflect( cameraToVertex, worldNormal );\n\t\t#else\n\t\t\tvReflect = refract( cameraToVertex, worldNormal, refractionRatio );\n\t\t#endif\n\t#endif\n#endif\n";

var fog_vertex = "\n#ifdef USE_FOG\nfogDepth = -mvPosition.z;\n#endif";

var fog_pars_vertex = "#ifdef USE_FOG\n  varying float fogDepth;\n#endif\n";

var fog_fragment = "#ifdef USE_FOG\n\t#ifdef FOG_EXP2\n\t\tfloat fogFactor = whiteCompliment( exp2( - fogDensity * fogDensity * fogDepth * fogDepth * LOG2 ) );\n\t#else\n\t\tfloat fogFactor = smoothstep( fogNear, fogFar, fogDepth );\n\t#endif\n\tgl_FragColor.rgb = mix( gl_FragColor.rgb, fogColor, fogFactor );\n#endif\n";

var fog_pars_fragment = "#ifdef USE_FOG\n\tuniform vec3 fogColor;\n\tvarying float fogDepth;\n\t#ifdef FOG_EXP2\n\t\tuniform float fogDensity;\n\t#else\n\t\tuniform float fogNear;\n\t\tuniform float fogFar;\n\t#endif\n#endif\n";

var gradientmap_pars_fragment = "#ifdef TOON\n\tuniform sampler2D gradientMap;\n\tvec3 getGradientIrradiance( vec3 normal, vec3 lightDirection ) {\n\t\tfloat dotNL = dot( normal, lightDirection );\n\t\tvec2 coord = vec2( dotNL * 0.5 + 0.5, 0.0 );\n\t\t#ifdef USE_GRADIENTMAP\n\t\t\treturn texture2D( gradientMap, coord ).rgb;\n\t\t#else\n\t\t\treturn ( coord.x < 0.7 ) ? vec3( 0.7 ) : vec3( 1.0 );\n\t\t#endif\n\t}\n#endif\n";

var lightmap_fragment = "#ifdef USE_LIGHTMAP\n\treflectedLight.indirectDiffuse += PI * texture2D( lightMap, vUv2 ).xyz * lightMapIntensity;\n#endif\n";

var lightmap_pars_fragment = "#ifdef USE_LIGHTMAP\n\tuniform sampler2D lightMap;\n\tuniform float lightMapIntensity;\n#endif";

var lights_lambert_vertex = "vec3 diffuse = vec3( 1.0 );\nGeometricContext geometry;\ngeometry.position = mvPosition.xyz;\ngeometry.normal = normalize( transformedNormal );\ngeometry.viewDir = normalize( -mvPosition.xyz );\nGeometricContext backGeometry;\nbackGeometry.position = geometry.position;\nbackGeometry.normal = -geometry.normal;\nbackGeometry.viewDir = geometry.viewDir;\nvLightFront = vec3( 0.0 );\n#ifdef DOUBLE_SIDED\n\tvLightBack = vec3( 0.0 );\n#endif\nIncidentLight directLight;\nfloat dotNL;\nvec3 directLightColor_Diffuse;\n#if NUM_POINT_LIGHTS > 0\n\tfor ( int i = 0; i < NUM_POINT_LIGHTS; i ++ ) {\n\t\tgetPointDirectLightIrradiance( pointLights[ i ], geometry, directLight );\n\t\tdotNL = dot( geometry.normal, directLight.direction );\n\t\tdirectLightColor_Diffuse = PI * directLight.color;\n\t\tvLightFront += saturate( dotNL ) * directLightColor_Diffuse;\n\t\t#ifdef DOUBLE_SIDED\n\t\t\tvLightBack += saturate( -dotNL ) * directLightColor_Diffuse;\n\t\t#endif\n\t}\n#endif\n#if NUM_SPOT_LIGHTS > 0\n\tfor ( int i = 0; i < NUM_SPOT_LIGHTS; i ++ ) {\n\t\tgetSpotDirectLightIrradiance( spotLights[ i ], geometry, directLight );\n\t\tdotNL = dot( geometry.normal, directLight.direction );\n\t\tdirectLightColor_Diffuse = PI * directLight.color;\n\t\tvLightFront += saturate( dotNL ) * directLightColor_Diffuse;\n\t\t#ifdef DOUBLE_SIDED\n\t\t\tvLightBack += saturate( -dotNL ) * directLightColor_Diffuse;\n\t\t#endif\n\t}\n#endif\n#if NUM_DIR_LIGHTS > 0\n\tfor ( int i = 0; i < NUM_DIR_LIGHTS; i ++ ) {\n\t\tgetDirectionalDirectLightIrradiance( directionalLights[ i ], geometry, directLight );\n\t\tdotNL = dot( geometry.normal, directLight.direction );\n\t\tdirectLightColor_Diffuse = PI * directLight.color;\n\t\tvLightFront += saturate( dotNL ) * directLightColor_Diffuse;\n\t\t#ifdef DOUBLE_SIDED\n\t\t\tvLightBack += saturate( -dotNL ) * directLightColor_Diffuse;\n\t\t#endif\n\t}\n#endif\n#if NUM_HEMI_LIGHTS > 0\n\tfor ( int i = 0; i < NUM_HEMI_LIGHTS; i ++ ) {\n\t\tvLightFront += getHemisphereLightIrradiance( hemisphereLights[ i ], geometry );\n\t\t#ifdef DOUBLE_SIDED\n\t\t\tvLightBack += getHemisphereLightIrradiance( hemisphereLights[ i ], backGeometry );\n\t\t#endif\n\t}\n#endif\n";

var lights_pars = "uniform vec3 ambientLightColor;\nvec3 getAmbientLightIrradiance( const in vec3 ambientLightColor ) {\n\tvec3 irradiance = ambientLightColor;\n\t#ifndef PHYSICALLY_CORRECT_LIGHTS\n\t\tirradiance *= PI;\n\t#endif\n\treturn irradiance;\n}\n#if NUM_DIR_LIGHTS > 0\n\tstruct DirectionalLight {\n\t\tvec3 direction;\n\t\tvec3 color;\n\t\tint shadow;\n\t\tfloat shadowBias;\n\t\tfloat shadowRadius;\n\t\tvec2 shadowMapSize;\n\t};\n\tuniform DirectionalLight directionalLights[ NUM_DIR_LIGHTS ];\n\tvoid getDirectionalDirectLightIrradiance( const in DirectionalLight directionalLight, const in GeometricContext geometry, out IncidentLight directLight ) {\n\t\tdirectLight.color = directionalLight.color;\n\t\tdirectLight.direction = directionalLight.direction;\n\t\tdirectLight.visible = true;\n\t}\n#endif\n#if NUM_POINT_LIGHTS > 0\n\tstruct PointLight {\n\t\tvec3 position;\n\t\tvec3 color;\n\t\tfloat distance;\n\t\tfloat decay;\n\t\tint shadow;\n\t\tfloat shadowBias;\n\t\tfloat shadowRadius;\n\t\tvec2 shadowMapSize;\n\t};\n\tuniform PointLight pointLights[ NUM_POINT_LIGHTS ];\n\tvoid getPointDirectLightIrradiance( const in PointLight pointLight, const in GeometricContext geometry, out IncidentLight directLight ) {\n\t\tvec3 lVector = pointLight.position - geometry.position;\n\t\tdirectLight.direction = normalize( lVector );\n\t\tfloat lightDistance = length( lVector );\n\t\tdirectLight.color = pointLight.color;\n\t\tdirectLight.color *= punctualLightIntensityToIrradianceFactor( lightDistance, pointLight.distance, pointLight.decay );\n\t\tdirectLight.visible = ( directLight.color != vec3( 0.0 ) );\n\t}\n#endif\n#if NUM_SPOT_LIGHTS > 0\n\tstruct SpotLight {\n\t\tvec3 position;\n\t\tvec3 direction;\n\t\tvec3 color;\n\t\tfloat distance;\n\t\tfloat decay;\n\t\tfloat coneCos;\n\t\tfloat penumbraCos;\n\t\tint shadow;\n\t\tfloat shadowBias;\n\t\tfloat shadowRadius;\n\t\tvec2 shadowMapSize;\n\t};\n\tuniform SpotLight spotLights[ NUM_SPOT_LIGHTS ];\n\tvoid getSpotDirectLightIrradiance( const in SpotLight spotLight, const in GeometricContext geometry, out IncidentLight directLight  ) {\n\t\tvec3 lVector = spotLight.position - geometry.position;\n\t\tdirectLight.direction = normalize( lVector );\n\t\tfloat lightDistance = length( lVector );\n\t\tfloat angleCos = dot( directLight.direction, spotLight.direction );\n\t\tif ( angleCos > spotLight.coneCos ) {\n\t\t\tfloat spotEffect = smoothstep( spotLight.coneCos, spotLight.penumbraCos, angleCos );\n\t\t\tdirectLight.color = spotLight.color;\n\t\t\tdirectLight.color *= spotEffect * punctualLightIntensityToIrradianceFactor( lightDistance, spotLight.distance, spotLight.decay );\n\t\t\tdirectLight.visible = true;\n\t\t} else {\n\t\t\tdirectLight.color = vec3( 0.0 );\n\t\t\tdirectLight.visible = false;\n\t\t}\n\t}\n#endif\n#if NUM_RECT_AREA_LIGHTS > 0\n\tstruct RectAreaLight {\n\t\tvec3 color;\n\t\tvec3 position;\n\t\tvec3 halfWidth;\n\t\tvec3 halfHeight;\n\t};\n\tuniform sampler2D ltcMat;\tuniform sampler2D ltcMag;\n\tuniform RectAreaLight rectAreaLights[ NUM_RECT_AREA_LIGHTS ];\n#endif\n#if NUM_HEMI_LIGHTS > 0\n\tstruct HemisphereLight {\n\t\tvec3 direction;\n\t\tvec3 skyColor;\n\t\tvec3 groundColor;\n\t};\n\tuniform HemisphereLight hemisphereLights[ NUM_HEMI_LIGHTS ];\n\tvec3 getHemisphereLightIrradiance( const in HemisphereLight hemiLight, const in GeometricContext geometry ) {\n\t\tfloat dotNL = dot( geometry.normal, hemiLight.direction );\n\t\tfloat hemiDiffuseWeight = 0.5 * dotNL + 0.5;\n\t\tvec3 irradiance = mix( hemiLight.groundColor, hemiLight.skyColor, hemiDiffuseWeight );\n\t\t#ifndef PHYSICALLY_CORRECT_LIGHTS\n\t\t\tirradiance *= PI;\n\t\t#endif\n\t\treturn irradiance;\n\t}\n#endif\n#if defined( USE_ENVMAP ) && defined( PHYSICAL )\n\tvec3 getLightProbeIndirectIrradiance( const in GeometricContext geometry, const in int maxMIPLevel ) {\n\t\tvec3 worldNormal = inverseTransformDirection( geometry.normal, viewMatrix );\n\t\t#ifdef ENVMAP_TYPE_CUBE\n\t\t\tvec3 queryVec = vec3( flipEnvMap * worldNormal.x, worldNormal.yz );\n\t\t\t#ifdef TEXTURE_LOD_EXT\n\t\t\t\tvec4 envMapColor = textureCubeLodEXT( envMap, queryVec, float( maxMIPLevel ) );\n\t\t\t#else\n\t\t\t\tvec4 envMapColor = textureCube( envMap, queryVec, float( maxMIPLevel ) );\n\t\t\t#endif\n\t\t\tenvMapColor.rgb = envMapTexelToLinear( envMapColor ).rgb;\n\t\t#elif defined( ENVMAP_TYPE_CUBE_UV )\n\t\t\tvec3 queryVec = vec3( flipEnvMap * worldNormal.x, worldNormal.yz );\n\t\t\tvec4 envMapColor = textureCubeUV( queryVec, 1.0 );\n\t\t#else\n\t\t\tvec4 envMapColor = vec4( 0.0 );\n\t\t#endif\n\t\treturn PI * envMapColor.rgb * envMapIntensity;\n\t}\n\tfloat getSpecularMIPLevel( const in float blinnShininessExponent, const in int maxMIPLevel ) {\n\t\tfloat maxMIPLevelScalar = float( maxMIPLevel );\n\t\tfloat desiredMIPLevel = maxMIPLevelScalar - 0.79248 - 0.5 * log2( pow2( blinnShininessExponent ) + 1.0 );\n\t\treturn clamp( desiredMIPLevel, 0.0, maxMIPLevelScalar );\n\t}\n\tvec3 getLightProbeIndirectRadiance( const in GeometricContext geometry, const in float blinnShininessExponent, const in int maxMIPLevel ) {\n\t\t#ifdef ENVMAP_MODE_REFLECTION\n\t\t\tvec3 reflectVec = reflect( -geometry.viewDir, geometry.normal );\n\t\t#else\n\t\t\tvec3 reflectVec = refract( -geometry.viewDir, geometry.normal, refractionRatio );\n\t\t#endif\n\t\treflectVec = inverseTransformDirection( reflectVec, viewMatrix );\n\t\tfloat specularMIPLevel = getSpecularMIPLevel( blinnShininessExponent, maxMIPLevel );\n\t\t#ifdef ENVMAP_TYPE_CUBE\n\t\t\tvec3 queryReflectVec = vec3( flipEnvMap * reflectVec.x, reflectVec.yz );\n\t\t\t#ifdef TEXTURE_LOD_EXT\n\t\t\t\tvec4 envMapColor = textureCubeLodEXT( envMap, queryReflectVec, specularMIPLevel );\n\t\t\t#else\n\t\t\t\tvec4 envMapColor = textureCube( envMap, queryReflectVec, specularMIPLevel );\n\t\t\t#endif\n\t\t\tenvMapColor.rgb = envMapTexelToLinear( envMapColor ).rgb;\n\t\t#elif defined( ENVMAP_TYPE_CUBE_UV )\n\t\t\tvec3 queryReflectVec = vec3( flipEnvMap * reflectVec.x, reflectVec.yz );\n\t\t\tvec4 envMapColor = textureCubeUV(queryReflectVec, BlinnExponentToGGXRoughness(blinnShininessExponent));\n\t\t#elif defined( ENVMAP_TYPE_EQUIREC )\n\t\t\tvec2 sampleUV;\n\t\t\tsampleUV.y = saturate( reflectVec.y * 0.5 + 0.5 );\n\t\t\tsampleUV.x = atan( reflectVec.z, reflectVec.x ) * RECIPROCAL_PI2 + 0.5;\n\t\t\t#ifdef TEXTURE_LOD_EXT\n\t\t\t\tvec4 envMapColor = texture2DLodEXT( envMap, sampleUV, specularMIPLevel );\n\t\t\t#else\n\t\t\t\tvec4 envMapColor = texture2D( envMap, sampleUV, specularMIPLevel );\n\t\t\t#endif\n\t\t\tenvMapColor.rgb = envMapTexelToLinear( envMapColor ).rgb;\n\t\t#elif defined( ENVMAP_TYPE_SPHERE )\n\t\t\tvec3 reflectView = normalize( ( viewMatrix * vec4( reflectVec, 0.0 ) ).xyz + vec3( 0.0,0.0,1.0 ) );\n\t\t\t#ifdef TEXTURE_LOD_EXT\n\t\t\t\tvec4 envMapColor = texture2DLodEXT( envMap, reflectView.xy * 0.5 + 0.5, specularMIPLevel );\n\t\t\t#else\n\t\t\t\tvec4 envMapColor = texture2D( envMap, reflectView.xy * 0.5 + 0.5, specularMIPLevel );\n\t\t\t#endif\n\t\t\tenvMapColor.rgb = envMapTexelToLinear( envMapColor ).rgb;\n\t\t#endif\n\t\treturn envMapColor.rgb * envMapIntensity;\n\t}\n#endif\n";

var lights_phong_fragment = "BlinnPhongMaterial material;\nmaterial.diffuseColor = diffuseColor.rgb;\nmaterial.specularColor = specular;\nmaterial.specularShininess = shininess;\nmaterial.specularStrength = specularStrength;\n";

var lights_phong_pars_fragment = "varying vec3 vViewPosition;\n#ifndef FLAT_SHADED\n\tvarying vec3 vNormal;\n#endif\nstruct BlinnPhongMaterial {\n\tvec3\tdiffuseColor;\n\tvec3\tspecularColor;\n\tfloat\tspecularShininess;\n\tfloat\tspecularStrength;\n};\n#if NUM_RECT_AREA_LIGHTS > 0\n\tvoid RE_Direct_RectArea_BlinnPhong( const in RectAreaLight rectAreaLight, const in GeometricContext geometry, const in BlinnPhongMaterial material, inout ReflectedLight reflectedLight ) {\n\t\tvec3 matDiffColor = material.diffuseColor;\n\t\tvec3 matSpecColor = material.specularColor;\n\t\tvec3 lightColor   = rectAreaLight.color;\n\t\tfloat roughness = BlinnExponentToGGXRoughness( material.specularShininess );\n\t\tvec3 spec = Rect_Area_Light_Specular_Reflectance(\n\t\t\t\tgeometry,\n\t\t\t\trectAreaLight.position, rectAreaLight.halfWidth, rectAreaLight.halfHeight,\n\t\t\t\troughness,\n\t\t\t\tltcMat, ltcMag );\n\t\tvec3 diff = Rect_Area_Light_Diffuse_Reflectance(\n\t\t\t\tgeometry,\n\t\t\t\trectAreaLight.position, rectAreaLight.halfWidth, rectAreaLight.halfHeight );\n\t\treflectedLight.directSpecular += lightColor * matSpecColor * spec / PI2;\n\t\treflectedLight.directDiffuse  += lightColor * matDiffColor * diff / PI2;\n\t}\n#endif\nvoid RE_Direct_BlinnPhong( const in IncidentLight directLight, const in GeometricContext geometry, const in BlinnPhongMaterial material, inout ReflectedLight reflectedLight ) {\n\t#ifdef TOON\n\t\tvec3 irradiance = getGradientIrradiance( geometry.normal, directLight.direction ) * directLight.color;\n\t#else\n\t\tfloat dotNL = saturate( dot( geometry.normal, directLight.direction ) );\n\t\tvec3 irradiance = dotNL * directLight.color;\n\t#endif\n\t#ifndef PHYSICALLY_CORRECT_LIGHTS\n\t\tirradiance *= PI;\n\t#endif\n\treflectedLight.directDiffuse += irradiance * BRDF_Diffuse_Lambert( material.diffuseColor );\n\treflectedLight.directSpecular += irradiance * BRDF_Specular_BlinnPhong( directLight, geometry, material.specularColor, material.specularShininess ) * material.specularStrength;\n}\nvoid RE_IndirectDiffuse_BlinnPhong( const in vec3 irradiance, const in GeometricContext geometry, const in BlinnPhongMaterial material, inout ReflectedLight reflectedLight ) {\n\treflectedLight.indirectDiffuse += irradiance * BRDF_Diffuse_Lambert( material.diffuseColor );\n}\n#define RE_Direct\t\t\t\tRE_Direct_BlinnPhong\n#define RE_Direct_RectArea\t\tRE_Direct_RectArea_BlinnPhong\n#define RE_IndirectDiffuse\t\tRE_IndirectDiffuse_BlinnPhong\n#define Material_LightProbeLOD( material )\t(0)\n";

var lights_physical_fragment = "PhysicalMaterial material;\nmaterial.diffuseColor = diffuseColor.rgb * ( 1.0 - metalnessFactor );\nmaterial.specularRoughness = clamp( roughnessFactor, 0.04, 1.0 );\n#ifdef STANDARD\n\tmaterial.specularColor = mix( vec3( DEFAULT_SPECULAR_COEFFICIENT ), diffuseColor.rgb, metalnessFactor );\n#else\n\tmaterial.specularColor = mix( vec3( MAXIMUM_SPECULAR_COEFFICIENT * pow2( reflectivity ) ), diffuseColor.rgb, metalnessFactor );\n\tmaterial.clearCoat = saturate( clearCoat );\tmaterial.clearCoatRoughness = clamp( clearCoatRoughness, 0.04, 1.0 );\n#endif\n";

var lights_physical_pars_fragment = "struct PhysicalMaterial {\n\tvec3\tdiffuseColor;\n\tfloat\tspecularRoughness;\n\tvec3\tspecularColor;\n\t#ifndef STANDARD\n\t\tfloat clearCoat;\n\t\tfloat clearCoatRoughness;\n\t#endif\n};\n#define MAXIMUM_SPECULAR_COEFFICIENT 0.16\n#define DEFAULT_SPECULAR_COEFFICIENT 0.04\nfloat clearCoatDHRApprox( const in float roughness, const in float dotNL ) {\n\treturn DEFAULT_SPECULAR_COEFFICIENT + ( 1.0 - DEFAULT_SPECULAR_COEFFICIENT ) * ( pow( 1.0 - dotNL, 5.0 ) * pow( 1.0 - roughness, 2.0 ) );\n}\n#if NUM_RECT_AREA_LIGHTS > 0\n\tvoid RE_Direct_RectArea_Physical( const in RectAreaLight rectAreaLight, const in GeometricContext geometry, const in PhysicalMaterial material, inout ReflectedLight reflectedLight ) {\n\t\tvec3 matDiffColor = material.diffuseColor;\n\t\tvec3 matSpecColor = material.specularColor;\n\t\tvec3 lightColor   = rectAreaLight.color;\n\t\tfloat roughness = material.specularRoughness;\n\t\tvec3 spec = Rect_Area_Light_Specular_Reflectance(\n\t\t\t\tgeometry,\n\t\t\t\trectAreaLight.position, rectAreaLight.halfWidth, rectAreaLight.halfHeight,\n\t\t\t\troughness,\n\t\t\t\tltcMat, ltcMag );\n\t\tvec3 diff = Rect_Area_Light_Diffuse_Reflectance(\n\t\t\t\tgeometry,\n\t\t\t\trectAreaLight.position, rectAreaLight.halfWidth, rectAreaLight.halfHeight );\n\t\treflectedLight.directSpecular += lightColor * matSpecColor * spec;\n\t\treflectedLight.directDiffuse  += lightColor * matDiffColor * diff;\n\t}\n#endif\nvoid RE_Direct_Physical( const in IncidentLight directLight, const in GeometricContext geometry, const in PhysicalMaterial material, inout ReflectedLight reflectedLight ) {\n\tfloat dotNL = saturate( dot( geometry.normal, directLight.direction ) );\n\tvec3 irradiance = dotNL * directLight.color;\n\t#ifndef PHYSICALLY_CORRECT_LIGHTS\n\t\tirradiance *= PI;\n\t#endif\n\t#ifndef STANDARD\n\t\tfloat clearCoatDHR = material.clearCoat * clearCoatDHRApprox( material.clearCoatRoughness, dotNL );\n\t#else\n\t\tfloat clearCoatDHR = 0.0;\n\t#endif\n\treflectedLight.directSpecular += ( 1.0 - clearCoatDHR ) * irradiance * BRDF_Specular_GGX( directLight, geometry, material.specularColor, material.specularRoughness );\n\treflectedLight.directDiffuse += ( 1.0 - clearCoatDHR ) * irradiance * BRDF_Diffuse_Lambert( material.diffuseColor );\n\t#ifndef STANDARD\n\t\treflectedLight.directSpecular += irradiance * material.clearCoat * BRDF_Specular_GGX( directLight, geometry, vec3( DEFAULT_SPECULAR_COEFFICIENT ), material.clearCoatRoughness );\n\t#endif\n}\nvoid RE_IndirectDiffuse_Physical( const in vec3 irradiance, const in GeometricContext geometry, const in PhysicalMaterial material, inout ReflectedLight reflectedLight ) {\n\treflectedLight.indirectDiffuse += irradiance * BRDF_Diffuse_Lambert( material.diffuseColor );\n}\nvoid RE_IndirectSpecular_Physical( const in vec3 radiance, const in vec3 clearCoatRadiance, const in GeometricContext geometry, const in PhysicalMaterial material, inout ReflectedLight reflectedLight ) {\n\t#ifndef STANDARD\n\t\tfloat dotNV = saturate( dot( geometry.normal, geometry.viewDir ) );\n\t\tfloat dotNL = dotNV;\n\t\tfloat clearCoatDHR = material.clearCoat * clearCoatDHRApprox( material.clearCoatRoughness, dotNL );\n\t#else\n\t\tfloat clearCoatDHR = 0.0;\n\t#endif\n\treflectedLight.indirectSpecular += ( 1.0 - clearCoatDHR ) * radiance * BRDF_Specular_GGX_Environment( geometry, material.specularColor, material.specularRoughness );\n\t#ifndef STANDARD\n\t\treflectedLight.indirectSpecular += clearCoatRadiance * material.clearCoat * BRDF_Specular_GGX_Environment( geometry, vec3( DEFAULT_SPECULAR_COEFFICIENT ), material.clearCoatRoughness );\n\t#endif\n}\n#define RE_Direct\t\t\t\tRE_Direct_Physical\n#define RE_Direct_RectArea\t\tRE_Direct_RectArea_Physical\n#define RE_IndirectDiffuse\t\tRE_IndirectDiffuse_Physical\n#define RE_IndirectSpecular\t\tRE_IndirectSpecular_Physical\n#define Material_BlinnShininessExponent( material )   GGXRoughnessToBlinnExponent( material.specularRoughness )\n#define Material_ClearCoat_BlinnShininessExponent( material )   GGXRoughnessToBlinnExponent( material.clearCoatRoughness )\nfloat computeSpecularOcclusion( const in float dotNV, const in float ambientOcclusion, const in float roughness ) {\n\treturn saturate( pow( dotNV + ambientOcclusion, exp2( - 16.0 * roughness - 1.0 ) ) - 1.0 + ambientOcclusion );\n}\n";

var lights_template = "\nGeometricContext geometry;\ngeometry.position = - vViewPosition;\ngeometry.normal = normal;\ngeometry.viewDir = normalize( vViewPosition );\nIncidentLight directLight;\n#if ( NUM_POINT_LIGHTS > 0 ) && defined( RE_Direct )\n\tPointLight pointLight;\n\tfor ( int i = 0; i < NUM_POINT_LIGHTS; i ++ ) {\n\t\tpointLight = pointLights[ i ];\n\t\tgetPointDirectLightIrradiance( pointLight, geometry, directLight );\n\t\t#ifdef USE_SHADOWMAP\n\t\tdirectLight.color *= all( bvec2( pointLight.shadow, directLight.visible ) ) ? getPointShadow( pointShadowMap[ i ], pointLight.shadowMapSize, pointLight.shadowBias, pointLight.shadowRadius, vPointShadowCoord[ i ] ) : 1.0;\n\t\t#endif\n\t\tRE_Direct( directLight, geometry, material, reflectedLight );\n\t}\n#endif\n#if ( NUM_SPOT_LIGHTS > 0 ) && defined( RE_Direct )\n\tSpotLight spotLight;\n\tfor ( int i = 0; i < NUM_SPOT_LIGHTS; i ++ ) {\n\t\tspotLight = spotLights[ i ];\n\t\tgetSpotDirectLightIrradiance( spotLight, geometry, directLight );\n\t\t#ifdef USE_SHADOWMAP\n\t\tdirectLight.color *= all( bvec2( spotLight.shadow, directLight.visible ) ) ? getShadow( spotShadowMap[ i ], spotLight.shadowMapSize, spotLight.shadowBias, spotLight.shadowRadius, vSpotShadowCoord[ i ] ) : 1.0;\n\t\t#endif\n\t\tRE_Direct( directLight, geometry, material, reflectedLight );\n\t}\n#endif\n#if ( NUM_DIR_LIGHTS > 0 ) && defined( RE_Direct )\n\tDirectionalLight directionalLight;\n\tfor ( int i = 0; i < NUM_DIR_LIGHTS; i ++ ) {\n\t\tdirectionalLight = directionalLights[ i ];\n\t\tgetDirectionalDirectLightIrradiance( directionalLight, geometry, directLight );\n\t\t#ifdef USE_SHADOWMAP\n\t\tdirectLight.color *= all( bvec2( directionalLight.shadow, directLight.visible ) ) ? getShadow( directionalShadowMap[ i ], directionalLight.shadowMapSize, directionalLight.shadowBias, directionalLight.shadowRadius, vDirectionalShadowCoord[ i ] ) : 1.0;\n\t\t#endif\n\t\tRE_Direct( directLight, geometry, material, reflectedLight );\n\t}\n#endif\n#if ( NUM_RECT_AREA_LIGHTS > 0 ) && defined( RE_Direct_RectArea )\n\tRectAreaLight rectAreaLight;\n\tfor ( int i = 0; i < NUM_RECT_AREA_LIGHTS; i ++ ) {\n\t\trectAreaLight = rectAreaLights[ i ];\n\t\tRE_Direct_RectArea( rectAreaLight, geometry, material, reflectedLight );\n\t}\n#endif\n#if defined( RE_IndirectDiffuse )\n\tvec3 irradiance = getAmbientLightIrradiance( ambientLightColor );\n\t#ifdef USE_LIGHTMAP\n\t\tvec3 lightMapIrradiance = texture2D( lightMap, vUv2 ).xyz * lightMapIntensity;\n\t\t#ifndef PHYSICALLY_CORRECT_LIGHTS\n\t\t\tlightMapIrradiance *= PI;\n\t\t#endif\n\t\tirradiance += lightMapIrradiance;\n\t#endif\n\t#if ( NUM_HEMI_LIGHTS > 0 )\n\t\tfor ( int i = 0; i < NUM_HEMI_LIGHTS; i ++ ) {\n\t\t\tirradiance += getHemisphereLightIrradiance( hemisphereLights[ i ], geometry );\n\t\t}\n\t#endif\n\t#if defined( USE_ENVMAP ) && defined( PHYSICAL ) && defined( ENVMAP_TYPE_CUBE_UV )\n\t\tirradiance += getLightProbeIndirectIrradiance( geometry, 8 );\n\t#endif\n\tRE_IndirectDiffuse( irradiance, geometry, material, reflectedLight );\n#endif\n#if defined( USE_ENVMAP ) && defined( RE_IndirectSpecular )\n\tvec3 radiance = getLightProbeIndirectRadiance( geometry, Material_BlinnShininessExponent( material ), 8 );\n\t#ifndef STANDARD\n\t\tvec3 clearCoatRadiance = getLightProbeIndirectRadiance( geometry, Material_ClearCoat_BlinnShininessExponent( material ), 8 );\n\t#else\n\t\tvec3 clearCoatRadiance = vec3( 0.0 );\n\t#endif\n\tRE_IndirectSpecular( radiance, clearCoatRadiance, geometry, material, reflectedLight );\n#endif\n";

var logdepthbuf_fragment = "#if defined(USE_LOGDEPTHBUF) && defined(USE_LOGDEPTHBUF_EXT)\n\tgl_FragDepthEXT = log2(vFragDepth) * logDepthBufFC * 0.5;\n#endif";

var logdepthbuf_pars_fragment = "#ifdef USE_LOGDEPTHBUF\n\tuniform float logDepthBufFC;\n\t#ifdef USE_LOGDEPTHBUF_EXT\n\t\tvarying float vFragDepth;\n\t#endif\n#endif\n";

var logdepthbuf_pars_vertex = "#ifdef USE_LOGDEPTHBUF\n\t#ifdef USE_LOGDEPTHBUF_EXT\n\t\tvarying float vFragDepth;\n\t#endif\n\tuniform float logDepthBufFC;\n#endif";

var logdepthbuf_vertex = "#ifdef USE_LOGDEPTHBUF\n\tgl_Position.z = log2(max( EPSILON, gl_Position.w + 1.0 )) * logDepthBufFC;\n\t#ifdef USE_LOGDEPTHBUF_EXT\n\t\tvFragDepth = 1.0 + gl_Position.w;\n\t#else\n\t\tgl_Position.z = (gl_Position.z - 1.0) * gl_Position.w;\n\t#endif\n#endif\n";

var map_fragment = "#ifdef USE_MAP\n\tvec4 texelColor = texture2D( map, vUv );\n\ttexelColor = mapTexelToLinear( texelColor );\n\tdiffuseColor *= texelColor;\n#endif\n";

var map_pars_fragment = "#ifdef USE_MAP\n\tuniform sampler2D map;\n#endif\n";

var map_particle_fragment = "#ifdef USE_MAP\n\tvec4 mapTexel = texture2D( map, vec2( gl_PointCoord.x, 1.0 - gl_PointCoord.y ) * offsetRepeat.zw + offsetRepeat.xy );\n\tdiffuseColor *= mapTexelToLinear( mapTexel );\n#endif\n";

var map_particle_pars_fragment = "#ifdef USE_MAP\n\tuniform vec4 offsetRepeat;\n\tuniform sampler2D map;\n#endif\n";

var metalnessmap_fragment = "float metalnessFactor = metalness;\n#ifdef USE_METALNESSMAP\n\tvec4 texelMetalness = texture2D( metalnessMap, vUv );\n\tmetalnessFactor *= texelMetalness.r;\n#endif\n";

var metalnessmap_pars_fragment = "#ifdef USE_METALNESSMAP\n\tuniform sampler2D metalnessMap;\n#endif";

var morphnormal_vertex = "#ifdef USE_MORPHNORMALS\n\tobjectNormal += ( morphNormal0 - normal ) * morphTargetInfluences[ 0 ];\n\tobjectNormal += ( morphNormal1 - normal ) * morphTargetInfluences[ 1 ];\n\tobjectNormal += ( morphNormal2 - normal ) * morphTargetInfluences[ 2 ];\n\tobjectNormal += ( morphNormal3 - normal ) * morphTargetInfluences[ 3 ];\n#endif\n";

var morphtarget_pars_vertex = "#ifdef USE_MORPHTARGETS\n\t#ifndef USE_MORPHNORMALS\n\tuniform float morphTargetInfluences[ 8 ];\n\t#else\n\tuniform float morphTargetInfluences[ 4 ];\n\t#endif\n#endif";

var morphtarget_vertex = "#ifdef USE_MORPHTARGETS\n\ttransformed += ( morphTarget0 - position ) * morphTargetInfluences[ 0 ];\n\ttransformed += ( morphTarget1 - position ) * morphTargetInfluences[ 1 ];\n\ttransformed += ( morphTarget2 - position ) * morphTargetInfluences[ 2 ];\n\ttransformed += ( morphTarget3 - position ) * morphTargetInfluences[ 3 ];\n\t#ifndef USE_MORPHNORMALS\n\ttransformed += ( morphTarget4 - position ) * morphTargetInfluences[ 4 ];\n\ttransformed += ( morphTarget5 - position ) * morphTargetInfluences[ 5 ];\n\ttransformed += ( morphTarget6 - position ) * morphTargetInfluences[ 6 ];\n\ttransformed += ( morphTarget7 - position ) * morphTargetInfluences[ 7 ];\n\t#endif\n#endif\n";

var normal_flip = "#ifdef DOUBLE_SIDED\n\tfloat flipNormal = ( float( gl_FrontFacing ) * 2.0 - 1.0 );\n#else\n\tfloat flipNormal = 1.0;\n#endif\n";

var normal_fragment = "#ifdef FLAT_SHADED\n\tvec3 fdx = vec3( dFdx( vViewPosition.x ), dFdx( vViewPosition.y ), dFdx( vViewPosition.z ) );\n\tvec3 fdy = vec3( dFdy( vViewPosition.x ), dFdy( vViewPosition.y ), dFdy( vViewPosition.z ) );\n\tvec3 normal = normalize( cross( fdx, fdy ) );\n#else\n\tvec3 normal = normalize( vNormal ) * flipNormal;\n#endif\n#ifdef USE_NORMALMAP\n\tnormal = perturbNormal2Arb( -vViewPosition, normal );\n#elif defined( USE_BUMPMAP )\n\tnormal = perturbNormalArb( -vViewPosition, normal, dHdxy_fwd() );\n#endif\n";

var normalmap_pars_fragment = "#ifdef USE_NORMALMAP\n\tuniform sampler2D normalMap;\n\tuniform vec2 normalScale;\n\tvec3 perturbNormal2Arb( vec3 eye_pos, vec3 surf_norm ) {\n\t\tvec3 q0 = dFdx( eye_pos.xyz );\n\t\tvec3 q1 = dFdy( eye_pos.xyz );\n\t\tvec2 st0 = dFdx( vUv.st );\n\t\tvec2 st1 = dFdy( vUv.st );\n\t\tvec3 S = normalize( q0 * st1.t - q1 * st0.t );\n\t\tvec3 T = normalize( -q0 * st1.s + q1 * st0.s );\n\t\tvec3 N = normalize( surf_norm );\n\t\tvec3 mapN = texture2D( normalMap, vUv ).xyz * 2.0 - 1.0;\n\t\tmapN.xy = normalScale * mapN.xy;\n\t\tmat3 tsn = mat3( S, T, N );\n\t\treturn normalize( tsn * mapN );\n\t}\n#endif\n";

var packing = "vec3 packNormalToRGB( const in vec3 normal ) {\n\treturn normalize( normal ) * 0.5 + 0.5;\n}\nvec3 unpackRGBToNormal( const in vec3 rgb ) {\n\treturn 1.0 - 2.0 * rgb.xyz;\n}\nconst float PackUpscale = 256. / 255.;const float UnpackDownscale = 255. / 256.;\nconst vec3 PackFactors = vec3( 256. * 256. * 256., 256. * 256.,  256. );\nconst vec4 UnpackFactors = UnpackDownscale / vec4( PackFactors, 1. );\nconst float ShiftRight8 = 1. / 256.;\nvec4 packDepthToRGBA( const in float v ) {\n\tvec4 r = vec4( fract( v * PackFactors ), v );\n\tr.yzw -= r.xyz * ShiftRight8;\treturn r * PackUpscale;\n}\nfloat unpackRGBAToDepth( const in vec4 v ) {\n\treturn dot( v, UnpackFactors );\n}\nfloat viewZToOrthographicDepth( const in float viewZ, const in float near, const in float far ) {\n\treturn ( viewZ + near ) / ( near - far );\n}\nfloat orthographicDepthToViewZ( const in float linearClipZ, const in float near, const in float far ) {\n\treturn linearClipZ * ( near - far ) - near;\n}\nfloat viewZToPerspectiveDepth( const in float viewZ, const in float near, const in float far ) {\n\treturn (( near + viewZ ) * far ) / (( far - near ) * viewZ );\n}\nfloat perspectiveDepthToViewZ( const in float invClipZ, const in float near, const in float far ) {\n\treturn ( near * far ) / ( ( far - near ) * invClipZ - far );\n}\n";

var premultiplied_alpha_fragment = "#ifdef PREMULTIPLIED_ALPHA\n\tgl_FragColor.rgb *= gl_FragColor.a;\n#endif\n";

var project_vertex = "#ifdef USE_SKINNING\n\tvec4 mvPosition = modelViewMatrix * skinned;\n#else\n\tvec4 mvPosition = modelViewMatrix * vec4( transformed, 1.0 );\n#endif\ngl_Position = projectionMatrix * mvPosition;\n";

var roughnessmap_fragment = "float roughnessFactor = roughness;\n#ifdef USE_ROUGHNESSMAP\n\tvec4 texelRoughness = texture2D( roughnessMap, vUv );\n\troughnessFactor *= texelRoughness.r;\n#endif\n";

var roughnessmap_pars_fragment = "#ifdef USE_ROUGHNESSMAP\n\tuniform sampler2D roughnessMap;\n#endif";

var shadowmap_pars_fragment = "#ifdef USE_SHADOWMAP\n\t#if NUM_DIR_LIGHTS > 0\n\t\tuniform sampler2D directionalShadowMap[ NUM_DIR_LIGHTS ];\n\t\tvarying vec4 vDirectionalShadowCoord[ NUM_DIR_LIGHTS ];\n\t#endif\n\t#if NUM_SPOT_LIGHTS > 0\n\t\tuniform sampler2D spotShadowMap[ NUM_SPOT_LIGHTS ];\n\t\tvarying vec4 vSpotShadowCoord[ NUM_SPOT_LIGHTS ];\n\t#endif\n\t#if NUM_POINT_LIGHTS > 0\n\t\tuniform sampler2D pointShadowMap[ NUM_POINT_LIGHTS ];\n\t\tvarying vec4 vPointShadowCoord[ NUM_POINT_LIGHTS ];\n\t#endif\n\tfloat texture2DCompare( sampler2D depths, vec2 uv, float compare ) {\n\t\treturn step( compare, unpackRGBAToDepth( texture2D( depths, uv ) ) );\n\t}\n\tfloat texture2DShadowLerp( sampler2D depths, vec2 size, vec2 uv, float compare ) {\n\t\tconst vec2 offset = vec2( 0.0, 1.0 );\n\t\tvec2 texelSize = vec2( 1.0 ) / size;\n\t\tvec2 centroidUV = floor( uv * size + 0.5 ) / size;\n\t\tfloat lb = texture2DCompare( depths, centroidUV + texelSize * offset.xx, compare );\n\t\tfloat lt = texture2DCompare( depths, centroidUV + texelSize * offset.xy, compare );\n\t\tfloat rb = texture2DCompare( depths, centroidUV + texelSize * offset.yx, compare );\n\t\tfloat rt = texture2DCompare( depths, centroidUV + texelSize * offset.yy, compare );\n\t\tvec2 f = fract( uv * size + 0.5 );\n\t\tfloat a = mix( lb, lt, f.y );\n\t\tfloat b = mix( rb, rt, f.y );\n\t\tfloat c = mix( a, b, f.x );\n\t\treturn c;\n\t}\n\tfloat getShadow( sampler2D shadowMap, vec2 shadowMapSize, float shadowBias, float shadowRadius, vec4 shadowCoord ) {\n\t\tshadowCoord.xyz /= shadowCoord.w;\n\t\tshadowCoord.z += shadowBias;\n\t\tbvec4 inFrustumVec = bvec4 ( shadowCoord.x >= 0.0, shadowCoord.x <= 1.0, shadowCoord.y >= 0.0, shadowCoord.y <= 1.0 );\n\t\tbool inFrustum = all( inFrustumVec );\n\t\tbvec2 frustumTestVec = bvec2( inFrustum, shadowCoord.z <= 1.0 );\n\t\tbool frustumTest = all( frustumTestVec );\n\t\tif ( frustumTest ) {\n\t\t#if defined( SHADOWMAP_TYPE_PCF )\n\t\t\tvec2 texelSize = vec2( 1.0 ) / shadowMapSize;\n\t\t\tfloat dx0 = - texelSize.x * shadowRadius;\n\t\t\tfloat dy0 = - texelSize.y * shadowRadius;\n\t\t\tfloat dx1 = + texelSize.x * shadowRadius;\n\t\t\tfloat dy1 = + texelSize.y * shadowRadius;\n\t\t\treturn (\n\t\t\t\ttexture2DCompare( shadowMap, shadowCoord.xy + vec2( dx0, dy0 ), shadowCoord.z ) +\n\t\t\t\ttexture2DCompare( shadowMap, shadowCoord.xy + vec2( 0.0, dy0 ), shadowCoord.z ) +\n\t\t\t\ttexture2DCompare( shadowMap, shadowCoord.xy + vec2( dx1, dy0 ), shadowCoord.z ) +\n\t\t\t\ttexture2DCompare( shadowMap, shadowCoord.xy + vec2( dx0, 0.0 ), shadowCoord.z ) +\n\t\t\t\ttexture2DCompare( shadowMap, shadowCoord.xy, shadowCoord.z ) +\n\t\t\t\ttexture2DCompare( shadowMap, shadowCoord.xy + vec2( dx1, 0.0 ), shadowCoord.z ) +\n\t\t\t\ttexture2DCompare( shadowMap, shadowCoord.xy + vec2( dx0, dy1 ), shadowCoord.z ) +\n\t\t\t\ttexture2DCompare( shadowMap, shadowCoord.xy + vec2( 0.0, dy1 ), shadowCoord.z ) +\n\t\t\t\ttexture2DCompare( shadowMap, shadowCoord.xy + vec2( dx1, dy1 ), shadowCoord.z )\n\t\t\t) * ( 1.0 / 9.0 );\n\t\t#elif defined( SHADOWMAP_TYPE_PCF_SOFT )\n\t\t\tvec2 texelSize = vec2( 1.0 ) / shadowMapSize;\n\t\t\tfloat dx0 = - texelSize.x * shadowRadius;\n\t\t\tfloat dy0 = - texelSize.y * shadowRadius;\n\t\t\tfloat dx1 = + texelSize.x * shadowRadius;\n\t\t\tfloat dy1 = + texelSize.y * shadowRadius;\n\t\t\treturn (\n\t\t\t\ttexture2DShadowLerp( shadowMap, shadowMapSize, shadowCoord.xy + vec2( dx0, dy0 ), shadowCoord.z ) +\n\t\t\t\ttexture2DShadowLerp( shadowMap, shadowMapSize, shadowCoord.xy + vec2( 0.0, dy0 ), shadowCoord.z ) +\n\t\t\t\ttexture2DShadowLerp( shadowMap, shadowMapSize, shadowCoord.xy + vec2( dx1, dy0 ), shadowCoord.z ) +\n\t\t\t\ttexture2DShadowLerp( shadowMap, shadowMapSize, shadowCoord.xy + vec2( dx0, 0.0 ), shadowCoord.z ) +\n\t\t\t\ttexture2DShadowLerp( shadowMap, shadowMapSize, shadowCoord.xy, shadowCoord.z ) +\n\t\t\t\ttexture2DShadowLerp( shadowMap, shadowMapSize, shadowCoord.xy + vec2( dx1, 0.0 ), shadowCoord.z ) +\n\t\t\t\ttexture2DShadowLerp( shadowMap, shadowMapSize, shadowCoord.xy + vec2( dx0, dy1 ), shadowCoord.z ) +\n\t\t\t\ttexture2DShadowLerp( shadowMap, shadowMapSize, shadowCoord.xy + vec2( 0.0, dy1 ), shadowCoord.z ) +\n\t\t\t\ttexture2DShadowLerp( shadowMap, shadowMapSize, shadowCoord.xy + vec2( dx1, dy1 ), shadowCoord.z )\n\t\t\t) * ( 1.0 / 9.0 );\n\t\t#else\n\t\t\treturn texture2DCompare( shadowMap, shadowCoord.xy, shadowCoord.z );\n\t\t#endif\n\t\t}\n\t\treturn 1.0;\n\t}\n\tvec2 cubeToUV( vec3 v, float texelSizeY ) {\n\t\tvec3 absV = abs( v );\n\t\tfloat scaleToCube = 1.0 / max( absV.x, max( absV.y, absV.z ) );\n\t\tabsV *= scaleToCube;\n\t\tv *= scaleToCube * ( 1.0 - 2.0 * texelSizeY );\n\t\tvec2 planar = v.xy;\n\t\tfloat almostATexel = 1.5 * texelSizeY;\n\t\tfloat almostOne = 1.0 - almostATexel;\n\t\tif ( absV.z >= almostOne ) {\n\t\t\tif ( v.z > 0.0 )\n\t\t\t\tplanar.x = 4.0 - v.x;\n\t\t} else if ( absV.x >= almostOne ) {\n\t\t\tfloat signX = sign( v.x );\n\t\t\tplanar.x = v.z * signX + 2.0 * signX;\n\t\t} else if ( absV.y >= almostOne ) {\n\t\t\tfloat signY = sign( v.y );\n\t\t\tplanar.x = v.x + 2.0 * signY + 2.0;\n\t\t\tplanar.y = v.z * signY - 2.0;\n\t\t}\n\t\treturn vec2( 0.125, 0.25 ) * planar + vec2( 0.375, 0.75 );\n\t}\n\tfloat getPointShadow( sampler2D shadowMap, vec2 shadowMapSize, float shadowBias, float shadowRadius, vec4 shadowCoord ) {\n\t\tvec2 texelSize = vec2( 1.0 ) / ( shadowMapSize * vec2( 4.0, 2.0 ) );\n\t\tvec3 lightToPosition = shadowCoord.xyz;\n\t\tvec3 bd3D = normalize( lightToPosition );\n\t\tfloat dp = ( length( lightToPosition ) - shadowBias ) / 1000.0;\n\t\t#if defined( SHADOWMAP_TYPE_PCF ) || defined( SHADOWMAP_TYPE_PCF_SOFT )\n\t\t\tvec2 offset = vec2( - 1, 1 ) * shadowRadius * texelSize.y;\n\t\t\treturn (\n\t\t\t\ttexture2DCompare( shadowMap, cubeToUV( bd3D + offset.xyy, texelSize.y ), dp ) +\n\t\t\t\ttexture2DCompare( shadowMap, cubeToUV( bd3D + offset.yyy, texelSize.y ), dp ) +\n\t\t\t\ttexture2DCompare( shadowMap, cubeToUV( bd3D + offset.xyx, texelSize.y ), dp ) +\n\t\t\t\ttexture2DCompare( shadowMap, cubeToUV( bd3D + offset.yyx, texelSize.y ), dp ) +\n\t\t\t\ttexture2DCompare( shadowMap, cubeToUV( bd3D, texelSize.y ), dp ) +\n\t\t\t\ttexture2DCompare( shadowMap, cubeToUV( bd3D + offset.xxy, texelSize.y ), dp ) +\n\t\t\t\ttexture2DCompare( shadowMap, cubeToUV( bd3D + offset.yxy, texelSize.y ), dp ) +\n\t\t\t\ttexture2DCompare( shadowMap, cubeToUV( bd3D + offset.xxx, texelSize.y ), dp ) +\n\t\t\t\ttexture2DCompare( shadowMap, cubeToUV( bd3D + offset.yxx, texelSize.y ), dp )\n\t\t\t) * ( 1.0 / 9.0 );\n\t\t#else\n\t\t\treturn texture2DCompare( shadowMap, cubeToUV( bd3D, texelSize.y ), dp );\n\t\t#endif\n\t}\n#endif\n";

var shadowmap_pars_vertex = "#ifdef USE_SHADOWMAP\n\t#if NUM_DIR_LIGHTS > 0\n\t\tuniform mat4 directionalShadowMatrix[ NUM_DIR_LIGHTS ];\n\t\tvarying vec4 vDirectionalShadowCoord[ NUM_DIR_LIGHTS ];\n\t#endif\n\t#if NUM_SPOT_LIGHTS > 0\n\t\tuniform mat4 spotShadowMatrix[ NUM_SPOT_LIGHTS ];\n\t\tvarying vec4 vSpotShadowCoord[ NUM_SPOT_LIGHTS ];\n\t#endif\n\t#if NUM_POINT_LIGHTS > 0\n\t\tuniform mat4 pointShadowMatrix[ NUM_POINT_LIGHTS ];\n\t\tvarying vec4 vPointShadowCoord[ NUM_POINT_LIGHTS ];\n\t#endif\n#endif\n";

var shadowmap_vertex = "#ifdef USE_SHADOWMAP\n\t#if NUM_DIR_LIGHTS > 0\n\tfor ( int i = 0; i < NUM_DIR_LIGHTS; i ++ ) {\n\t\tvDirectionalShadowCoord[ i ] = directionalShadowMatrix[ i ] * worldPosition;\n\t}\n\t#endif\n\t#if NUM_SPOT_LIGHTS > 0\n\tfor ( int i = 0; i < NUM_SPOT_LIGHTS; i ++ ) {\n\t\tvSpotShadowCoord[ i ] = spotShadowMatrix[ i ] * worldPosition;\n\t}\n\t#endif\n\t#if NUM_POINT_LIGHTS > 0\n\tfor ( int i = 0; i < NUM_POINT_LIGHTS; i ++ ) {\n\t\tvPointShadowCoord[ i ] = pointShadowMatrix[ i ] * worldPosition;\n\t}\n\t#endif\n#endif\n";

var shadowmask_pars_fragment = "float getShadowMask() {\n\tfloat shadow = 1.0;\n\t#ifdef USE_SHADOWMAP\n\t#if NUM_DIR_LIGHTS > 0\n\tDirectionalLight directionalLight;\n\tfor ( int i = 0; i < NUM_DIR_LIGHTS; i ++ ) {\n\t\tdirectionalLight = directionalLights[ i ];\n\t\tshadow *= bool( directionalLight.shadow ) ? getShadow( directionalShadowMap[ i ], directionalLight.shadowMapSize, directionalLight.shadowBias, directionalLight.shadowRadius, vDirectionalShadowCoord[ i ] ) : 1.0;\n\t}\n\t#endif\n\t#if NUM_SPOT_LIGHTS > 0\n\tSpotLight spotLight;\n\tfor ( int i = 0; i < NUM_SPOT_LIGHTS; i ++ ) {\n\t\tspotLight = spotLights[ i ];\n\t\tshadow *= bool( spotLight.shadow ) ? getShadow( spotShadowMap[ i ], spotLight.shadowMapSize, spotLight.shadowBias, spotLight.shadowRadius, vSpotShadowCoord[ i ] ) : 1.0;\n\t}\n\t#endif\n\t#if NUM_POINT_LIGHTS > 0\n\tPointLight pointLight;\n\tfor ( int i = 0; i < NUM_POINT_LIGHTS; i ++ ) {\n\t\tpointLight = pointLights[ i ];\n\t\tshadow *= bool( pointLight.shadow ) ? getPointShadow( pointShadowMap[ i ], pointLight.shadowMapSize, pointLight.shadowBias, pointLight.shadowRadius, vPointShadowCoord[ i ] ) : 1.0;\n\t}\n\t#endif\n\t#endif\n\treturn shadow;\n}\n";

var skinbase_vertex = "#ifdef USE_SKINNING\n\tmat4 boneMatX = getBoneMatrix( skinIndex.x );\n\tmat4 boneMatY = getBoneMatrix( skinIndex.y );\n\tmat4 boneMatZ = getBoneMatrix( skinIndex.z );\n\tmat4 boneMatW = getBoneMatrix( skinIndex.w );\n#endif";

var skinning_pars_vertex = "#ifdef USE_SKINNING\n\tuniform mat4 bindMatrix;\n\tuniform mat4 bindMatrixInverse;\n\t#ifdef BONE_TEXTURE\n\t\tuniform sampler2D boneTexture;\n\t\tuniform int boneTextureWidth;\n\t\tuniform int boneTextureHeight;\n\t\tmat4 getBoneMatrix( const in float i ) {\n\t\t\tfloat j = i * 4.0;\n\t\t\tfloat x = mod( j, float( boneTextureWidth ) );\n\t\t\tfloat y = floor( j / float( boneTextureWidth ) );\n\t\t\tfloat dx = 1.0 / float( boneTextureWidth );\n\t\t\tfloat dy = 1.0 / float( boneTextureHeight );\n\t\t\ty = dy * ( y + 0.5 );\n\t\t\tvec4 v1 = texture2D( boneTexture, vec2( dx * ( x + 0.5 ), y ) );\n\t\t\tvec4 v2 = texture2D( boneTexture, vec2( dx * ( x + 1.5 ), y ) );\n\t\t\tvec4 v3 = texture2D( boneTexture, vec2( dx * ( x + 2.5 ), y ) );\n\t\t\tvec4 v4 = texture2D( boneTexture, vec2( dx * ( x + 3.5 ), y ) );\n\t\t\tmat4 bone = mat4( v1, v2, v3, v4 );\n\t\t\treturn bone;\n\t\t}\n\t#else\n\t\tuniform mat4 boneMatrices[ MAX_BONES ];\n\t\tmat4 getBoneMatrix( const in float i ) {\n\t\t\tmat4 bone = boneMatrices[ int(i) ];\n\t\t\treturn bone;\n\t\t}\n\t#endif\n#endif\n";

var skinning_vertex = "#ifdef USE_SKINNING\n\tvec4 skinVertex = bindMatrix * vec4( transformed, 1.0 );\n\tvec4 skinned = vec4( 0.0 );\n\tskinned += boneMatX * skinVertex * skinWeight.x;\n\tskinned += boneMatY * skinVertex * skinWeight.y;\n\tskinned += boneMatZ * skinVertex * skinWeight.z;\n\tskinned += boneMatW * skinVertex * skinWeight.w;\n\tskinned  = bindMatrixInverse * skinned;\n#endif\n";

var skinnormal_vertex = "#ifdef USE_SKINNING\n\tmat4 skinMatrix = mat4( 0.0 );\n\tskinMatrix += skinWeight.x * boneMatX;\n\tskinMatrix += skinWeight.y * boneMatY;\n\tskinMatrix += skinWeight.z * boneMatZ;\n\tskinMatrix += skinWeight.w * boneMatW;\n\tskinMatrix  = bindMatrixInverse * skinMatrix * bindMatrix;\n\tobjectNormal = vec4( skinMatrix * vec4( objectNormal, 0.0 ) ).xyz;\n#endif\n";

var specularmap_fragment = "float specularStrength;\n#ifdef USE_SPECULARMAP\n\tvec4 texelSpecular = texture2D( specularMap, vUv );\n\tspecularStrength = texelSpecular.r;\n#else\n\tspecularStrength = 1.0;\n#endif";

var specularmap_pars_fragment = "#ifdef USE_SPECULARMAP\n\tuniform sampler2D specularMap;\n#endif";

var tonemapping_fragment = "#if defined( TONE_MAPPING )\n  gl_FragColor.rgb = toneMapping( gl_FragColor.rgb );\n#endif\n";

var tonemapping_pars_fragment = "#define saturate(a) clamp( a, 0.0, 1.0 )\nuniform float toneMappingExposure;\nuniform float toneMappingWhitePoint;\nvec3 LinearToneMapping( vec3 color ) {\n\treturn toneMappingExposure * color;\n}\nvec3 ReinhardToneMapping( vec3 color ) {\n\tcolor *= toneMappingExposure;\n\treturn saturate( color / ( vec3( 1.0 ) + color ) );\n}\n#define Uncharted2Helper( x ) max( ( ( x * ( 0.15 * x + 0.10 * 0.50 ) + 0.20 * 0.02 ) / ( x * ( 0.15 * x + 0.50 ) + 0.20 * 0.30 ) ) - 0.02 / 0.30, vec3( 0.0 ) )\nvec3 Uncharted2ToneMapping( vec3 color ) {\n\tcolor *= toneMappingExposure;\n\treturn saturate( Uncharted2Helper( color ) / Uncharted2Helper( vec3( toneMappingWhitePoint ) ) );\n}\nvec3 OptimizedCineonToneMapping( vec3 color ) {\n\tcolor *= toneMappingExposure;\n\tcolor = max( vec3( 0.0 ), color - 0.004 );\n\treturn pow( ( color * ( 6.2 * color + 0.5 ) ) / ( color * ( 6.2 * color + 1.7 ) + 0.06 ), vec3( 2.2 ) );\n}\n";

var uv_pars_fragment = "#if defined( USE_MAP ) || defined( USE_BUMPMAP ) || defined( USE_NORMALMAP ) || defined( USE_SPECULARMAP ) || defined( USE_ALPHAMAP ) || defined( USE_EMISSIVEMAP ) || defined( USE_ROUGHNESSMAP ) || defined( USE_METALNESSMAP )\n\tvarying vec2 vUv;\n#endif";

var uv_pars_vertex = "#if defined( USE_MAP ) || defined( USE_BUMPMAP ) || defined( USE_NORMALMAP ) || defined( USE_SPECULARMAP ) || defined( USE_ALPHAMAP ) || defined( USE_EMISSIVEMAP ) || defined( USE_ROUGHNESSMAP ) || defined( USE_METALNESSMAP )\n\tvarying vec2 vUv;\n\tuniform vec4 offsetRepeat;\n#endif\n";

var uv_vertex = "#if defined( USE_MAP ) || defined( USE_BUMPMAP ) || defined( USE_NORMALMAP ) || defined( USE_SPECULARMAP ) || defined( USE_ALPHAMAP ) || defined( USE_EMISSIVEMAP ) || defined( USE_ROUGHNESSMAP ) || defined( USE_METALNESSMAP )\n\tvUv = uv * offsetRepeat.zw + offsetRepeat.xy;\n#endif";

var uv2_pars_fragment = "#if defined( USE_LIGHTMAP ) || defined( USE_AOMAP )\n\tvarying vec2 vUv2;\n#endif";

var uv2_pars_vertex = "#if defined( USE_LIGHTMAP ) || defined( USE_AOMAP )\n\tattribute vec2 uv2;\n\tvarying vec2 vUv2;\n#endif";

var uv2_vertex = "#if defined( USE_LIGHTMAP ) || defined( USE_AOMAP )\n\tvUv2 = uv2;\n#endif";

var worldpos_vertex = "#if defined( USE_ENVMAP ) || defined( PHONG ) || defined( PHYSICAL ) || defined( LAMBERT ) || defined ( USE_SHADOWMAP )\n\t#ifdef USE_SKINNING\n\t\tvec4 worldPosition = modelMatrix * skinned;\n\t#else\n\t\tvec4 worldPosition = modelMatrix * vec4( transformed, 1.0 );\n\t#endif\n#endif\n";

var cube_frag = "uniform samplerCube tCube;\nuniform float tFlip;\nuniform float opacity;\nvarying vec3 vWorldPosition;\n#include <common>\nvoid main() {\n\tgl_FragColor = textureCube( tCube, vec3( tFlip * vWorldPosition.x, vWorldPosition.yz ) );\n\tgl_FragColor.a *= opacity;\n}\n";

var cube_vert = "varying vec3 vWorldPosition;\n#include <common>\nvoid main() {\n\tvWorldPosition = transformDirection( position, modelMatrix );\n\t#include <begin_vertex>\n\t#include <project_vertex>\n}\n";

var depth_frag = "#if DEPTH_PACKING == 3200\n\tuniform float opacity;\n#endif\n#include <common>\n#include <packing>\n#include <uv_pars_fragment>\n#include <map_pars_fragment>\n#include <alphamap_pars_fragment>\n#include <logdepthbuf_pars_fragment>\n#include <clipping_planes_pars_fragment>\nvoid main() {\n\t#include <clipping_planes_fragment>\n\tvec4 diffuseColor = vec4( 1.0 );\n\t#if DEPTH_PACKING == 3200\n\t\tdiffuseColor.a = opacity;\n\t#endif\n\t#include <map_fragment>\n\t#include <alphamap_fragment>\n\t#include <alphatest_fragment>\n\t#include <logdepthbuf_fragment>\n\t#if DEPTH_PACKING == 3200\n\t\tgl_FragColor = vec4( vec3( gl_FragCoord.z ), opacity );\n\t#elif DEPTH_PACKING == 3201\n\t\tgl_FragColor = packDepthToRGBA( gl_FragCoord.z );\n\t#endif\n}\n";

var depth_vert = "#include <common>\n#include <uv_pars_vertex>\n#include <displacementmap_pars_vertex>\n#include <morphtarget_pars_vertex>\n#include <skinning_pars_vertex>\n#include <logdepthbuf_pars_vertex>\n#include <clipping_planes_pars_vertex>\nvoid main() {\n\t#include <uv_vertex>\n\t#include <skinbase_vertex>\n\t#include <begin_vertex>\n\t#include <displacementmap_vertex>\n\t#include <morphtarget_vertex>\n\t#include <skinning_vertex>\n\t#include <project_vertex>\n\t#include <logdepthbuf_vertex>\n\t#include <clipping_planes_vertex>\n}\n";

var distanceRGBA_frag = "uniform vec3 lightPos;\nvarying vec4 vWorldPosition;\n#include <common>\n#include <packing>\n#include <clipping_planes_pars_fragment>\nvoid main () {\n\t#include <clipping_planes_fragment>\n\tgl_FragColor = packDepthToRGBA( length( vWorldPosition.xyz - lightPos.xyz ) / 1000.0 );\n}\n";

var distanceRGBA_vert = "varying vec4 vWorldPosition;\n#include <common>\n#include <morphtarget_pars_vertex>\n#include <skinning_pars_vertex>\n#include <clipping_planes_pars_vertex>\nvoid main() {\n\t#include <skinbase_vertex>\n\t#include <begin_vertex>\n\t#include <morphtarget_vertex>\n\t#include <skinning_vertex>\n\t#include <project_vertex>\n\t#include <worldpos_vertex>\n\t#include <clipping_planes_vertex>\n\tvWorldPosition = worldPosition;\n}\n";

var equirect_frag = "uniform sampler2D tEquirect;\nuniform float tFlip;\nvarying vec3 vWorldPosition;\n#include <common>\nvoid main() {\n\tvec3 direction = normalize( vWorldPosition );\n\tvec2 sampleUV;\n\tsampleUV.y = saturate( tFlip * direction.y * -0.5 + 0.5 );\n\tsampleUV.x = atan( direction.z, direction.x ) * RECIPROCAL_PI2 + 0.5;\n\tgl_FragColor = texture2D( tEquirect, sampleUV );\n}\n";

var equirect_vert = "varying vec3 vWorldPosition;\n#include <common>\nvoid main() {\n\tvWorldPosition = transformDirection( position, modelMatrix );\n\t#include <begin_vertex>\n\t#include <project_vertex>\n}\n";

var linedashed_frag = "uniform vec3 diffuse;\nuniform float opacity;\nuniform float dashSize;\nuniform float totalSize;\nvarying float vLineDistance;\n#include <common>\n#include <color_pars_fragment>\n#include <fog_pars_fragment>\n#include <logdepthbuf_pars_fragment>\n#include <clipping_planes_pars_fragment>\nvoid main() {\n\t#include <clipping_planes_fragment>\n\tif ( mod( vLineDistance, totalSize ) > dashSize ) {\n\t\tdiscard;\n\t}\n\tvec3 outgoingLight = vec3( 0.0 );\n\tvec4 diffuseColor = vec4( diffuse, opacity );\n\t#include <logdepthbuf_fragment>\n\t#include <color_fragment>\n\toutgoingLight = diffuseColor.rgb;\n\tgl_FragColor = vec4( outgoingLight, diffuseColor.a );\n\t#include <premultiplied_alpha_fragment>\n\t#include <tonemapping_fragment>\n\t#include <encodings_fragment>\n\t#include <fog_fragment>\n}\n";

var linedashed_vert = "uniform float scale;\nattribute float lineDistance;\nvarying float vLineDistance;\n#include <common>\n#include <color_pars_vertex>\n#include <fog_pars_vertex>\n#include <logdepthbuf_pars_vertex>\n#include <clipping_planes_pars_vertex>\nvoid main() {\n\t#include <color_vertex>\n\tvLineDistance = scale * lineDistance;\n\tvec4 mvPosition = modelViewMatrix * vec4( position, 1.0 );\n\tgl_Position = projectionMatrix * mvPosition;\n\t#include <logdepthbuf_vertex>\n\t#include <clipping_planes_vertex>\n\t#include <fog_vertex>\n}\n";

var meshbasic_frag = "uniform vec3 diffuse;\nuniform float opacity;\n#ifndef FLAT_SHADED\n\tvarying vec3 vNormal;\n#endif\n#include <common>\n#include <color_pars_fragment>\n#include <uv_pars_fragment>\n#include <uv2_pars_fragment>\n#include <map_pars_fragment>\n#include <alphamap_pars_fragment>\n#include <aomap_pars_fragment>\n#include <lightmap_pars_fragment>\n#include <envmap_pars_fragment>\n#include <fog_pars_fragment>\n#include <specularmap_pars_fragment>\n#include <logdepthbuf_pars_fragment>\n#include <clipping_planes_pars_fragment>\nvoid main() {\n\t#include <clipping_planes_fragment>\n\tvec4 diffuseColor = vec4( diffuse, opacity );\n\t#include <logdepthbuf_fragment>\n\t#include <map_fragment>\n\t#include <color_fragment>\n\t#include <alphamap_fragment>\n\t#include <alphatest_fragment>\n\t#include <specularmap_fragment>\n\tReflectedLight reflectedLight = ReflectedLight( vec3( 0.0 ), vec3( 0.0 ), vec3( 0.0 ), vec3( 0.0 ) );\n\t#ifdef USE_LIGHTMAP\n\t\treflectedLight.indirectDiffuse += texture2D( lightMap, vUv2 ).xyz * lightMapIntensity;\n\t#else\n\t\treflectedLight.indirectDiffuse += vec3( 1.0 );\n\t#endif\n\t#include <aomap_fragment>\n\treflectedLight.indirectDiffuse *= diffuseColor.rgb;\n\tvec3 outgoingLight = reflectedLight.indirectDiffuse;\n\t#include <normal_flip>\n\t#include <envmap_fragment>\n\tgl_FragColor = vec4( outgoingLight, diffuseColor.a );\n\t#include <premultiplied_alpha_fragment>\n\t#include <tonemapping_fragment>\n\t#include <encodings_fragment>\n\t#include <fog_fragment>\n}\n";

var meshbasic_vert = "#include <common>\n#include <uv_pars_vertex>\n#include <uv2_pars_vertex>\n#include <envmap_pars_vertex>\n#include <color_pars_vertex>\n#include <fog_pars_vertex>\n#include <morphtarget_pars_vertex>\n#include <skinning_pars_vertex>\n#include <logdepthbuf_pars_vertex>\n#include <clipping_planes_pars_vertex>\nvoid main() {\n\t#include <uv_vertex>\n\t#include <uv2_vertex>\n\t#include <color_vertex>\n\t#include <skinbase_vertex>\n\t#ifdef USE_ENVMAP\n\t#include <beginnormal_vertex>\n\t#include <morphnormal_vertex>\n\t#include <skinnormal_vertex>\n\t#include <defaultnormal_vertex>\n\t#endif\n\t#include <begin_vertex>\n\t#include <morphtarget_vertex>\n\t#include <skinning_vertex>\n\t#include <project_vertex>\n\t#include <logdepthbuf_vertex>\n\t#include <worldpos_vertex>\n\t#include <clipping_planes_vertex>\n\t#include <envmap_vertex>\n\t#include <fog_vertex>\n}\n";

var meshlambert_frag = "uniform vec3 diffuse;\nuniform vec3 emissive;\nuniform float opacity;\nvarying vec3 vLightFront;\n#ifdef DOUBLE_SIDED\n\tvarying vec3 vLightBack;\n#endif\n#include <common>\n#include <packing>\n#include <color_pars_fragment>\n#include <uv_pars_fragment>\n#include <uv2_pars_fragment>\n#include <map_pars_fragment>\n#include <alphamap_pars_fragment>\n#include <aomap_pars_fragment>\n#include <lightmap_pars_fragment>\n#include <emissivemap_pars_fragment>\n#include <envmap_pars_fragment>\n#include <bsdfs>\n#include <lights_pars>\n#include <fog_pars_fragment>\n#include <shadowmap_pars_fragment>\n#include <shadowmask_pars_fragment>\n#include <specularmap_pars_fragment>\n#include <logdepthbuf_pars_fragment>\n#include <clipping_planes_pars_fragment>\nvoid main() {\n\t#include <clipping_planes_fragment>\n\tvec4 diffuseColor = vec4( diffuse, opacity );\n\tReflectedLight reflectedLight = ReflectedLight( vec3( 0.0 ), vec3( 0.0 ), vec3( 0.0 ), vec3( 0.0 ) );\n\tvec3 totalEmissiveRadiance = emissive;\n\t#include <logdepthbuf_fragment>\n\t#include <map_fragment>\n\t#include <color_fragment>\n\t#include <alphamap_fragment>\n\t#include <alphatest_fragment>\n\t#include <specularmap_fragment>\n\t#include <emissivemap_fragment>\n\treflectedLight.indirectDiffuse = getAmbientLightIrradiance( ambientLightColor );\n\t#include <lightmap_fragment>\n\treflectedLight.indirectDiffuse *= BRDF_Diffuse_Lambert( diffuseColor.rgb );\n\t#ifdef DOUBLE_SIDED\n\t\treflectedLight.directDiffuse = ( gl_FrontFacing ) ? vLightFront : vLightBack;\n\t#else\n\t\treflectedLight.directDiffuse = vLightFront;\n\t#endif\n\treflectedLight.directDiffuse *= BRDF_Diffuse_Lambert( diffuseColor.rgb ) * getShadowMask();\n\t#include <aomap_fragment>\n\tvec3 outgoingLight = reflectedLight.directDiffuse + reflectedLight.indirectDiffuse + totalEmissiveRadiance;\n\t#include <normal_flip>\n\t#include <envmap_fragment>\n\tgl_FragColor = vec4( outgoingLight, diffuseColor.a );\n\t#include <premultiplied_alpha_fragment>\n\t#include <tonemapping_fragment>\n\t#include <encodings_fragment>\n\t#include <fog_fragment>\n}\n";

var meshlambert_vert = "#define LAMBERT\nvarying vec3 vLightFront;\n#ifdef DOUBLE_SIDED\n\tvarying vec3 vLightBack;\n#endif\n#include <common>\n#include <uv_pars_vertex>\n#include <uv2_pars_vertex>\n#include <envmap_pars_vertex>\n#include <bsdfs>\n#include <lights_pars>\n#include <color_pars_vertex>\n#include <fog_pars_vertex>\n#include <morphtarget_pars_vertex>\n#include <skinning_pars_vertex>\n#include <shadowmap_pars_vertex>\n#include <logdepthbuf_pars_vertex>\n#include <clipping_planes_pars_vertex>\nvoid main() {\n\t#include <uv_vertex>\n\t#include <uv2_vertex>\n\t#include <color_vertex>\n\t#include <beginnormal_vertex>\n\t#include <morphnormal_vertex>\n\t#include <skinbase_vertex>\n\t#include <skinnormal_vertex>\n\t#include <defaultnormal_vertex>\n\t#include <begin_vertex>\n\t#include <morphtarget_vertex>\n\t#include <skinning_vertex>\n\t#include <project_vertex>\n\t#include <logdepthbuf_vertex>\n\t#include <clipping_planes_vertex>\n\t#include <worldpos_vertex>\n\t#include <envmap_vertex>\n\t#include <lights_lambert_vertex>\n\t#include <shadowmap_vertex>\n\t#include <fog_vertex>\n}\n";

var meshphong_frag = "#define PHONG\nuniform vec3 diffuse;\nuniform vec3 emissive;\nuniform vec3 specular;\nuniform float shininess;\nuniform float opacity;\n#include <common>\n#include <packing>\n#include <color_pars_fragment>\n#include <uv_pars_fragment>\n#include <uv2_pars_fragment>\n#include <map_pars_fragment>\n#include <alphamap_pars_fragment>\n#include <aomap_pars_fragment>\n#include <lightmap_pars_fragment>\n#include <emissivemap_pars_fragment>\n#include <envmap_pars_fragment>\n#include <gradientmap_pars_fragment>\n#include <fog_pars_fragment>\n#include <bsdfs>\n#include <lights_pars>\n#include <lights_phong_pars_fragment>\n#include <shadowmap_pars_fragment>\n#include <bumpmap_pars_fragment>\n#include <normalmap_pars_fragment>\n#include <specularmap_pars_fragment>\n#include <logdepthbuf_pars_fragment>\n#include <clipping_planes_pars_fragment>\nvoid main() {\n\t#include <clipping_planes_fragment>\n\tvec4 diffuseColor = vec4( diffuse, opacity );\n\tReflectedLight reflectedLight = ReflectedLight( vec3( 0.0 ), vec3( 0.0 ), vec3( 0.0 ), vec3( 0.0 ) );\n\tvec3 totalEmissiveRadiance = emissive;\n\t#include <logdepthbuf_fragment>\n\t#include <map_fragment>\n\t#include <color_fragment>\n\t#include <alphamap_fragment>\n\t#include <alphatest_fragment>\n\t#include <specularmap_fragment>\n\t#include <normal_flip>\n\t#include <normal_fragment>\n\t#include <emissivemap_fragment>\n\t#include <lights_phong_fragment>\n\t#include <lights_template>\n\t#include <aomap_fragment>\n\tvec3 outgoingLight = reflectedLight.directDiffuse + reflectedLight.indirectDiffuse + reflectedLight.directSpecular + reflectedLight.indirectSpecular + totalEmissiveRadiance;\n\t#include <envmap_fragment>\n\tgl_FragColor = vec4( outgoingLight, diffuseColor.a );\n\t#include <premultiplied_alpha_fragment>\n\t#include <tonemapping_fragment>\n\t#include <encodings_fragment>\n\t#include <fog_fragment>\n}\n";

var meshphong_vert = "#define PHONG\nvarying vec3 vViewPosition;\n#ifndef FLAT_SHADED\n\tvarying vec3 vNormal;\n#endif\n#include <common>\n#include <uv_pars_vertex>\n#include <uv2_pars_vertex>\n#include <displacementmap_pars_vertex>\n#include <envmap_pars_vertex>\n#include <color_pars_vertex>\n#include <fog_pars_vertex>\n#include <morphtarget_pars_vertex>\n#include <skinning_pars_vertex>\n#include <shadowmap_pars_vertex>\n#include <logdepthbuf_pars_vertex>\n#include <clipping_planes_pars_vertex>\nvoid main() {\n\t#include <uv_vertex>\n\t#include <uv2_vertex>\n\t#include <color_vertex>\n\t#include <beginnormal_vertex>\n\t#include <morphnormal_vertex>\n\t#include <skinbase_vertex>\n\t#include <skinnormal_vertex>\n\t#include <defaultnormal_vertex>\n#ifndef FLAT_SHADED\n\tvNormal = normalize( transformedNormal );\n#endif\n\t#include <begin_vertex>\n\t#include <displacementmap_vertex>\n\t#include <morphtarget_vertex>\n\t#include <skinning_vertex>\n\t#include <project_vertex>\n\t#include <logdepthbuf_vertex>\n\t#include <clipping_planes_vertex>\n\tvViewPosition = - mvPosition.xyz;\n\t#include <worldpos_vertex>\n\t#include <envmap_vertex>\n\t#include <shadowmap_vertex>\n\t#include <fog_vertex>\n}\n";

var meshphysical_frag = "#define PHYSICAL\nuniform vec3 diffuse;\nuniform vec3 emissive;\nuniform float roughness;\nuniform float metalness;\nuniform float opacity;\n#ifndef STANDARD\n\tuniform float clearCoat;\n\tuniform float clearCoatRoughness;\n#endif\nvarying vec3 vViewPosition;\n#ifndef FLAT_SHADED\n\tvarying vec3 vNormal;\n#endif\n#include <common>\n#include <packing>\n#include <color_pars_fragment>\n#include <uv_pars_fragment>\n#include <uv2_pars_fragment>\n#include <map_pars_fragment>\n#include <alphamap_pars_fragment>\n#include <aomap_pars_fragment>\n#include <lightmap_pars_fragment>\n#include <emissivemap_pars_fragment>\n#include <envmap_pars_fragment>\n#include <fog_pars_fragment>\n#include <bsdfs>\n#include <cube_uv_reflection_fragment>\n#include <lights_pars>\n#include <lights_physical_pars_fragment>\n#include <shadowmap_pars_fragment>\n#include <bumpmap_pars_fragment>\n#include <normalmap_pars_fragment>\n#include <roughnessmap_pars_fragment>\n#include <metalnessmap_pars_fragment>\n#include <logdepthbuf_pars_fragment>\n#include <clipping_planes_pars_fragment>\nvoid main() {\n\t#include <clipping_planes_fragment>\n\tvec4 diffuseColor = vec4( diffuse, opacity );\n\tReflectedLight reflectedLight = ReflectedLight( vec3( 0.0 ), vec3( 0.0 ), vec3( 0.0 ), vec3( 0.0 ) );\n\tvec3 totalEmissiveRadiance = emissive;\n\t#include <logdepthbuf_fragment>\n\t#include <map_fragment>\n\t#include <color_fragment>\n\t#include <alphamap_fragment>\n\t#include <alphatest_fragment>\n\t#include <specularmap_fragment>\n\t#include <roughnessmap_fragment>\n\t#include <metalnessmap_fragment>\n\t#include <normal_flip>\n\t#include <normal_fragment>\n\t#include <emissivemap_fragment>\n\t#include <lights_physical_fragment>\n\t#include <lights_template>\n\t#include <aomap_fragment>\n\tvec3 outgoingLight = reflectedLight.directDiffuse + reflectedLight.indirectDiffuse + reflectedLight.directSpecular + reflectedLight.indirectSpecular + totalEmissiveRadiance;\n\tgl_FragColor = vec4( outgoingLight, diffuseColor.a );\n\t#include <premultiplied_alpha_fragment>\n\t#include <tonemapping_fragment>\n\t#include <encodings_fragment>\n\t#include <fog_fragment>\n}\n";

var meshphysical_vert = "#define PHYSICAL\nvarying vec3 vViewPosition;\n#ifndef FLAT_SHADED\n\tvarying vec3 vNormal;\n#endif\n#include <common>\n#include <uv_pars_vertex>\n#include <uv2_pars_vertex>\n#include <displacementmap_pars_vertex>\n#include <color_pars_vertex>\n#include <fog_pars_vertex>\n#include <morphtarget_pars_vertex>\n#include <skinning_pars_vertex>\n#include <shadowmap_pars_vertex>\n#include <specularmap_pars_fragment>\n#include <logdepthbuf_pars_vertex>\n#include <clipping_planes_pars_vertex>\nvoid main() {\n\t#include <uv_vertex>\n\t#include <uv2_vertex>\n\t#include <color_vertex>\n\t#include <beginnormal_vertex>\n\t#include <morphnormal_vertex>\n\t#include <skinbase_vertex>\n\t#include <skinnormal_vertex>\n\t#include <defaultnormal_vertex>\n#ifndef FLAT_SHADED\n\tvNormal = normalize( transformedNormal );\n#endif\n\t#include <begin_vertex>\n\t#include <displacementmap_vertex>\n\t#include <morphtarget_vertex>\n\t#include <skinning_vertex>\n\t#include <project_vertex>\n\t#include <logdepthbuf_vertex>\n\t#include <clipping_planes_vertex>\n\tvViewPosition = - mvPosition.xyz;\n\t#include <worldpos_vertex>\n\t#include <shadowmap_vertex>\n\t#include <fog_vertex>\n}\n";

var normal_frag = "#define NORMAL\nuniform float opacity;\n#if defined( FLAT_SHADED ) || defined( USE_BUMPMAP ) || defined( USE_NORMALMAP )\n\tvarying vec3 vViewPosition;\n#endif\n#ifndef FLAT_SHADED\n\tvarying vec3 vNormal;\n#endif\n#include <packing>\n#include <uv_pars_fragment>\n#include <bumpmap_pars_fragment>\n#include <normalmap_pars_fragment>\n#include <logdepthbuf_pars_fragment>\nvoid main() {\n\t#include <logdepthbuf_fragment>\n\t#include <normal_flip>\n\t#include <normal_fragment>\n\tgl_FragColor = vec4( packNormalToRGB( normal ), opacity );\n}\n";

var normal_vert = "#define NORMAL\n#if defined( FLAT_SHADED ) || defined( USE_BUMPMAP ) || defined( USE_NORMALMAP )\n\tvarying vec3 vViewPosition;\n#endif\n#ifndef FLAT_SHADED\n\tvarying vec3 vNormal;\n#endif\n#include <uv_pars_vertex>\n#include <displacementmap_pars_vertex>\n#include <morphtarget_pars_vertex>\n#include <skinning_pars_vertex>\n#include <logdepthbuf_pars_vertex>\nvoid main() {\n\t#include <uv_vertex>\n\t#include <beginnormal_vertex>\n\t#include <morphnormal_vertex>\n\t#include <skinbase_vertex>\n\t#include <skinnormal_vertex>\n\t#include <defaultnormal_vertex>\n#ifndef FLAT_SHADED\n\tvNormal = normalize( transformedNormal );\n#endif\n\t#include <begin_vertex>\n\t#include <displacementmap_vertex>\n\t#include <morphtarget_vertex>\n\t#include <skinning_vertex>\n\t#include <project_vertex>\n\t#include <logdepthbuf_vertex>\n#if defined( FLAT_SHADED ) || defined( USE_BUMPMAP ) || defined( USE_NORMALMAP )\n\tvViewPosition = - mvPosition.xyz;\n#endif\n}\n";

var points_frag = "uniform vec3 diffuse;\nuniform float opacity;\n#include <common>\n#include <packing>\n#include <color_pars_fragment>\n#include <map_particle_pars_fragment>\n#include <fog_pars_fragment>\n#include <shadowmap_pars_fragment>\n#include <logdepthbuf_pars_fragment>\n#include <clipping_planes_pars_fragment>\nvoid main() {\n\t#include <clipping_planes_fragment>\n\tvec3 outgoingLight = vec3( 0.0 );\n\tvec4 diffuseColor = vec4( diffuse, opacity );\n\t#include <logdepthbuf_fragment>\n\t#include <map_particle_fragment>\n\t#include <color_fragment>\n\t#include <alphatest_fragment>\n\toutgoingLight = diffuseColor.rgb;\n\tgl_FragColor = vec4( outgoingLight, diffuseColor.a );\n\t#include <premultiplied_alpha_fragment>\n\t#include <tonemapping_fragment>\n\t#include <encodings_fragment>\n\t#include <fog_fragment>\n}\n";

var points_vert = "uniform float size;\nuniform float scale;\n#include <common>\n#include <color_pars_vertex>\n#include <fog_pars_vertex>\n#include <shadowmap_pars_vertex>\n#include <logdepthbuf_pars_vertex>\n#include <clipping_planes_pars_vertex>\nvoid main() {\n\t#include <color_vertex>\n\t#include <begin_vertex>\n\t#include <project_vertex>\n\t#ifdef USE_SIZEATTENUATION\n\t\tgl_PointSize = size * ( scale / - mvPosition.z );\n\t#else\n\t\tgl_PointSize = size;\n\t#endif\n\t#include <logdepthbuf_vertex>\n\t#include <clipping_planes_vertex>\n\t#include <worldpos_vertex>\n\t#include <shadowmap_vertex>\n\t#include <fog_vertex>\n}\n";

var shadow_frag = "uniform float opacity;\n#include <common>\n#include <packing>\n#include <bsdfs>\n#include <lights_pars>\n#include <shadowmap_pars_fragment>\n#include <shadowmask_pars_fragment>\nvoid main() {\n\tgl_FragColor = vec4( 0.0, 0.0, 0.0, opacity * ( 1.0 - getShadowMask() ) );\n}\n";

var shadow_vert = "#include <shadowmap_pars_vertex>\nvoid main() {\n\t#include <begin_vertex>\n\t#include <project_vertex>\n\t#include <worldpos_vertex>\n\t#include <shadowmap_vertex>\n}\n";

var ShaderChunk = {
	alphamap_fragment: alphamap_fragment,
	alphamap_pars_fragment: alphamap_pars_fragment,
	alphatest_fragment: alphatest_fragment,
	aomap_fragment: aomap_fragment,
	aomap_pars_fragment: aomap_pars_fragment,
	begin_vertex: begin_vertex,
	beginnormal_vertex: beginnormal_vertex,
	bsdfs: bsdfs,
	bumpmap_pars_fragment: bumpmap_pars_fragment,
	clipping_planes_fragment: clipping_planes_fragment,
	clipping_planes_pars_fragment: clipping_planes_pars_fragment,
	clipping_planes_pars_vertex: clipping_planes_pars_vertex,
	clipping_planes_vertex: clipping_planes_vertex,
	color_fragment: color_fragment,
	color_pars_fragment: color_pars_fragment,
	color_pars_vertex: color_pars_vertex,
	color_vertex: color_vertex,
	common: common,
	cube_uv_reflection_fragment: cube_uv_reflection_fragment,
	defaultnormal_vertex: defaultnormal_vertex,
	displacementmap_pars_vertex: displacementmap_pars_vertex,
	displacementmap_vertex: displacementmap_vertex,
	emissivemap_fragment: emissivemap_fragment,
	emissivemap_pars_fragment: emissivemap_pars_fragment,
	encodings_fragment: encodings_fragment,
	encodings_pars_fragment: encodings_pars_fragment,
	envmap_fragment: envmap_fragment,
	envmap_pars_fragment: envmap_pars_fragment,
	envmap_pars_vertex: envmap_pars_vertex,
	envmap_vertex: envmap_vertex,
	fog_vertex: fog_vertex,
	fog_pars_vertex: fog_pars_vertex,
	fog_fragment: fog_fragment,
	fog_pars_fragment: fog_pars_fragment,
	gradientmap_pars_fragment: gradientmap_pars_fragment,
	lightmap_fragment: lightmap_fragment,
	lightmap_pars_fragment: lightmap_pars_fragment,
	lights_lambert_vertex: lights_lambert_vertex,
	lights_pars: lights_pars,
	lights_phong_fragment: lights_phong_fragment,
	lights_phong_pars_fragment: lights_phong_pars_fragment,
	lights_physical_fragment: lights_physical_fragment,
	lights_physical_pars_fragment: lights_physical_pars_fragment,
	lights_template: lights_template,
	logdepthbuf_fragment: logdepthbuf_fragment,
	logdepthbuf_pars_fragment: logdepthbuf_pars_fragment,
	logdepthbuf_pars_vertex: logdepthbuf_pars_vertex,
	logdepthbuf_vertex: logdepthbuf_vertex,
	map_fragment: map_fragment,
	map_pars_fragment: map_pars_fragment,
	map_particle_fragment: map_particle_fragment,
	map_particle_pars_fragment: map_particle_pars_fragment,
	metalnessmap_fragment: metalnessmap_fragment,
	metalnessmap_pars_fragment: metalnessmap_pars_fragment,
	morphnormal_vertex: morphnormal_vertex,
	morphtarget_pars_vertex: morphtarget_pars_vertex,
	morphtarget_vertex: morphtarget_vertex,
	normal_flip: normal_flip,
	normal_fragment: normal_fragment,
	normalmap_pars_fragment: normalmap_pars_fragment,
	packing: packing,
	premultiplied_alpha_fragment: premultiplied_alpha_fragment,
	project_vertex: project_vertex,
	roughnessmap_fragment: roughnessmap_fragment,
	roughnessmap_pars_fragment: roughnessmap_pars_fragment,
	shadowmap_pars_fragment: shadowmap_pars_fragment,
	shadowmap_pars_vertex: shadowmap_pars_vertex,
	shadowmap_vertex: shadowmap_vertex,
	shadowmask_pars_fragment: shadowmask_pars_fragment,
	skinbase_vertex: skinbase_vertex,
	skinning_pars_vertex: skinning_pars_vertex,
	skinning_vertex: skinning_vertex,
	skinnormal_vertex: skinnormal_vertex,
	specularmap_fragment: specularmap_fragment,
	specularmap_pars_fragment: specularmap_pars_fragment,
	tonemapping_fragment: tonemapping_fragment,
	tonemapping_pars_fragment: tonemapping_pars_fragment,
	uv_pars_fragment: uv_pars_fragment,
	uv_pars_vertex: uv_pars_vertex,
	uv_vertex: uv_vertex,
	uv2_pars_fragment: uv2_pars_fragment,
	uv2_pars_vertex: uv2_pars_vertex,
	uv2_vertex: uv2_vertex,
	worldpos_vertex: worldpos_vertex,

	cube_frag: cube_frag,
	cube_vert: cube_vert,
	depth_frag: depth_frag,
	depth_vert: depth_vert,
	distanceRGBA_frag: distanceRGBA_frag,
	distanceRGBA_vert: distanceRGBA_vert,
	equirect_frag: equirect_frag,
	equirect_vert: equirect_vert,
	linedashed_frag: linedashed_frag,
	linedashed_vert: linedashed_vert,
	meshbasic_frag: meshbasic_frag,
	meshbasic_vert: meshbasic_vert,
	meshlambert_frag: meshlambert_frag,
	meshlambert_vert: meshlambert_vert,
	meshphong_frag: meshphong_frag,
	meshphong_vert: meshphong_vert,
	meshphysical_frag: meshphysical_frag,
	meshphysical_vert: meshphysical_vert,
	normal_frag: normal_frag,
	normal_vert: normal_vert,
	points_frag: points_frag,
	points_vert: points_vert,
	shadow_frag: shadow_frag,
	shadow_vert: shadow_vert
};

/**
 * @author supereggbert / http://www.paulbrunt.co.uk/
 * @author philogb / http://blog.thejit.org/
 * @author mikael emtinger / http://gomo.se/
 * @author egraether / http://egraether.com/
 * @author WestLangley / http://github.com/WestLangley
 */

function Vector4( x, y, z, w ) {

	this.x = x || 0;
	this.y = y || 0;
	this.z = z || 0;
	this.w = ( w !== undefined ) ? w : 1;

}

Vector4.prototype = {

	constructor: Vector4,

	isVector4: true,

	set: function ( x, y, z, w ) {

		this.x = x;
		this.y = y;
		this.z = z;
		this.w = w;

		return this;

	},

	setScalar: function ( scalar ) {

		this.x = scalar;
		this.y = scalar;
		this.z = scalar;
		this.w = scalar;

		return this;

	},

	setX: function ( x ) {

		this.x = x;

		return this;

	},

	setY: function ( y ) {

		this.y = y;

		return this;

	},

	setZ: function ( z ) {

		this.z = z;

		return this;

	},

	setW: function ( w ) {

		this.w = w;

		return this;

	},

	setComponent: function ( index, value ) {

		switch ( index ) {

			case 0: this.x = value; break;
			case 1: this.y = value; break;
			case 2: this.z = value; break;
			case 3: this.w = value; break;
			default: throw new Error( 'index is out of range: ' + index );

		}

		return this;

	},

	getComponent: function ( index ) {

		switch ( index ) {

			case 0: return this.x;
			case 1: return this.y;
			case 2: return this.z;
			case 3: return this.w;
			default: throw new Error( 'index is out of range: ' + index );

		}

	},

	clone: function () {

		return new this.constructor( this.x, this.y, this.z, this.w );

	},

	copy: function ( v ) {

		this.x = v.x;
		this.y = v.y;
		this.z = v.z;
		this.w = ( v.w !== undefined ) ? v.w : 1;

		return this;

	},

	add: function ( v, w ) {

		if ( w !== undefined ) {

			console.warn( 'THREE.Vector4: .add() now only accepts one argument. Use .addVectors( a, b ) instead.' );
			return this.addVectors( v, w );

		}

		this.x += v.x;
		this.y += v.y;
		this.z += v.z;
		this.w += v.w;

		return this;

	},

	addScalar: function ( s ) {

		this.x += s;
		this.y += s;
		this.z += s;
		this.w += s;

		return this;

	},

	addVectors: function ( a, b ) {

		this.x = a.x + b.x;
		this.y = a.y + b.y;
		this.z = a.z + b.z;
		this.w = a.w + b.w;

		return this;

	},

	addScaledVector: function ( v, s ) {

		this.x += v.x * s;
		this.y += v.y * s;
		this.z += v.z * s;
		this.w += v.w * s;

		return this;

	},

	sub: function ( v, w ) {

		if ( w !== undefined ) {

			console.warn( 'THREE.Vector4: .sub() now only accepts one argument. Use .subVectors( a, b ) instead.' );
			return this.subVectors( v, w );

		}

		this.x -= v.x;
		this.y -= v.y;
		this.z -= v.z;
		this.w -= v.w;

		return this;

	},

	subScalar: function ( s ) {

		this.x -= s;
		this.y -= s;
		this.z -= s;
		this.w -= s;

		return this;

	},

	subVectors: function ( a, b ) {

		this.x = a.x - b.x;
		this.y = a.y - b.y;
		this.z = a.z - b.z;
		this.w = a.w - b.w;

		return this;

	},

	multiplyScalar: function ( scalar ) {

		if ( isFinite( scalar ) ) {

			this.x *= scalar;
			this.y *= scalar;
			this.z *= scalar;
			this.w *= scalar;

		} else {

			this.x = 0;
			this.y = 0;
			this.z = 0;
			this.w = 0;

		}

		return this;

	},

	applyMatrix4: function ( m ) {

		var x = this.x, y = this.y, z = this.z, w = this.w;
		var e = m.elements;

		this.x = e[ 0 ] * x + e[ 4 ] * y + e[ 8 ] * z + e[ 12 ] * w;
		this.y = e[ 1 ] * x + e[ 5 ] * y + e[ 9 ] * z + e[ 13 ] * w;
		this.z = e[ 2 ] * x + e[ 6 ] * y + e[ 10 ] * z + e[ 14 ] * w;
		this.w = e[ 3 ] * x + e[ 7 ] * y + e[ 11 ] * z + e[ 15 ] * w;

		return this;

	},

	divideScalar: function ( scalar ) {

		return this.multiplyScalar( 1 / scalar );

	},

	setAxisAngleFromQuaternion: function ( q ) {

		// http://www.euclideanspace.com/maths/geometry/rotations/conversions/quaternionToAngle/index.htm

		// q is assumed to be normalized

		this.w = 2 * Math.acos( q.w );

		var s = Math.sqrt( 1 - q.w * q.w );

		if ( s < 0.0001 ) {

			 this.x = 1;
			 this.y = 0;
			 this.z = 0;

		} else {

			 this.x = q.x / s;
			 this.y = q.y / s;
			 this.z = q.z / s;

		}

		return this;

	},

	setAxisAngleFromRotationMatrix: function ( m ) {

		// http://www.euclideanspace.com/maths/geometry/rotations/conversions/matrixToAngle/index.htm

		// assumes the upper 3x3 of m is a pure rotation matrix (i.e, unscaled)

		var angle, x, y, z,		// variables for result
			epsilon = 0.01,		// margin to allow for rounding errors
			epsilon2 = 0.1,		// margin to distinguish between 0 and 180 degrees

			te = m.elements,

			m11 = te[ 0 ], m12 = te[ 4 ], m13 = te[ 8 ],
			m21 = te[ 1 ], m22 = te[ 5 ], m23 = te[ 9 ],
			m31 = te[ 2 ], m32 = te[ 6 ], m33 = te[ 10 ];

		if ( ( Math.abs( m12 - m21 ) < epsilon ) &&
		     ( Math.abs( m13 - m31 ) < epsilon ) &&
		     ( Math.abs( m23 - m32 ) < epsilon ) ) {

			// singularity found
			// first check for identity matrix which must have +1 for all terms
			// in leading diagonal and zero in other terms

			if ( ( Math.abs( m12 + m21 ) < epsilon2 ) &&
			     ( Math.abs( m13 + m31 ) < epsilon2 ) &&
			     ( Math.abs( m23 + m32 ) < epsilon2 ) &&
			     ( Math.abs( m11 + m22 + m33 - 3 ) < epsilon2 ) ) {

				// this singularity is identity matrix so angle = 0

				this.set( 1, 0, 0, 0 );

				return this; // zero angle, arbitrary axis

			}

			// otherwise this singularity is angle = 180

			angle = Math.PI;

			var xx = ( m11 + 1 ) / 2;
			var yy = ( m22 + 1 ) / 2;
			var zz = ( m33 + 1 ) / 2;
			var xy = ( m12 + m21 ) / 4;
			var xz = ( m13 + m31 ) / 4;
			var yz = ( m23 + m32 ) / 4;

			if ( ( xx > yy ) && ( xx > zz ) ) {

				// m11 is the largest diagonal term

				if ( xx < epsilon ) {

					x = 0;
					y = 0.707106781;
					z = 0.707106781;

				} else {

					x = Math.sqrt( xx );
					y = xy / x;
					z = xz / x;

				}

			} else if ( yy > zz ) {

				// m22 is the largest diagonal term

				if ( yy < epsilon ) {

					x = 0.707106781;
					y = 0;
					z = 0.707106781;

				} else {

					y = Math.sqrt( yy );
					x = xy / y;
					z = yz / y;

				}

			} else {

				// m33 is the largest diagonal term so base result on this

				if ( zz < epsilon ) {

					x = 0.707106781;
					y = 0.707106781;
					z = 0;

				} else {

					z = Math.sqrt( zz );
					x = xz / z;
					y = yz / z;

				}

			}

			this.set( x, y, z, angle );

			return this; // return 180 deg rotation

		}

		// as we have reached here there are no singularities so we can handle normally

		var s = Math.sqrt( ( m32 - m23 ) * ( m32 - m23 ) +
		                   ( m13 - m31 ) * ( m13 - m31 ) +
		                   ( m21 - m12 ) * ( m21 - m12 ) ); // used to normalize

		if ( Math.abs( s ) < 0.001 ) s = 1;

		// prevent divide by zero, should not happen if matrix is orthogonal and should be
		// caught by singularity test above, but I've left it in just in case

		this.x = ( m32 - m23 ) / s;
		this.y = ( m13 - m31 ) / s;
		this.z = ( m21 - m12 ) / s;
		this.w = Math.acos( ( m11 + m22 + m33 - 1 ) / 2 );

		return this;

	},

	min: function ( v ) {

		this.x = Math.min( this.x, v.x );
		this.y = Math.min( this.y, v.y );
		this.z = Math.min( this.z, v.z );
		this.w = Math.min( this.w, v.w );

		return this;

	},

	max: function ( v ) {

		this.x = Math.max( this.x, v.x );
		this.y = Math.max( this.y, v.y );
		this.z = Math.max( this.z, v.z );
		this.w = Math.max( this.w, v.w );

		return this;

	},

	clamp: function ( min, max ) {

		// This function assumes min < max, if this assumption isn't true it will not operate correctly

		this.x = Math.max( min.x, Math.min( max.x, this.x ) );
		this.y = Math.max( min.y, Math.min( max.y, this.y ) );
		this.z = Math.max( min.z, Math.min( max.z, this.z ) );
		this.w = Math.max( min.w, Math.min( max.w, this.w ) );

		return this;

	},

	clampScalar: function () {

		var min, max;

		return function clampScalar( minVal, maxVal ) {

			if ( min === undefined ) {

				min = new Vector4();
				max = new Vector4();

			}

			min.set( minVal, minVal, minVal, minVal );
			max.set( maxVal, maxVal, maxVal, maxVal );

			return this.clamp( min, max );

		};

	}(),

	floor: function () {

		this.x = Math.floor( this.x );
		this.y = Math.floor( this.y );
		this.z = Math.floor( this.z );
		this.w = Math.floor( this.w );

		return this;

	},

	ceil: function () {

		this.x = Math.ceil( this.x );
		this.y = Math.ceil( this.y );
		this.z = Math.ceil( this.z );
		this.w = Math.ceil( this.w );

		return this;

	},

	round: function () {

		this.x = Math.round( this.x );
		this.y = Math.round( this.y );
		this.z = Math.round( this.z );
		this.w = Math.round( this.w );

		return this;

	},

	roundToZero: function () {

		this.x = ( this.x < 0 ) ? Math.ceil( this.x ) : Math.floor( this.x );
		this.y = ( this.y < 0 ) ? Math.ceil( this.y ) : Math.floor( this.y );
		this.z = ( this.z < 0 ) ? Math.ceil( this.z ) : Math.floor( this.z );
		this.w = ( this.w < 0 ) ? Math.ceil( this.w ) : Math.floor( this.w );

		return this;

	},

	negate: function () {

		this.x = - this.x;
		this.y = - this.y;
		this.z = - this.z;
		this.w = - this.w;

		return this;

	},

	dot: function ( v ) {

		return this.x * v.x + this.y * v.y + this.z * v.z + this.w * v.w;

	},

	lengthSq: function () {

		return this.x * this.x + this.y * this.y + this.z * this.z + this.w * this.w;

	},

	length: function () {

		return Math.sqrt( this.x * this.x + this.y * this.y + this.z * this.z + this.w * this.w );

	},

	lengthManhattan: function () {

		return Math.abs( this.x ) + Math.abs( this.y ) + Math.abs( this.z ) + Math.abs( this.w );

	},

	normalize: function () {

		return this.divideScalar( this.length() );

	},

	setLength: function ( length ) {

		return this.multiplyScalar( length / this.length() );

	},

	lerp: function ( v, alpha ) {

		this.x += ( v.x - this.x ) * alpha;
		this.y += ( v.y - this.y ) * alpha;
		this.z += ( v.z - this.z ) * alpha;
		this.w += ( v.w - this.w ) * alpha;

		return this;

	},

	lerpVectors: function ( v1, v2, alpha ) {

		return this.subVectors( v2, v1 ).multiplyScalar( alpha ).add( v1 );

	},

	equals: function ( v ) {

		return ( ( v.x === this.x ) && ( v.y === this.y ) && ( v.z === this.z ) && ( v.w === this.w ) );

	},

	fromArray: function ( array, offset ) {

		if ( offset === undefined ) offset = 0;

		this.x = array[ offset ];
		this.y = array[ offset + 1 ];
		this.z = array[ offset + 2 ];
		this.w = array[ offset + 3 ];

		return this;

	},

	toArray: function ( array, offset ) {

		if ( array === undefined ) array = [];
		if ( offset === undefined ) offset = 0;

		array[ offset ] = this.x;
		array[ offset + 1 ] = this.y;
		array[ offset + 2 ] = this.z;
		array[ offset + 3 ] = this.w;

		return array;

	},

	fromBufferAttribute: function ( attribute, index, offset ) {

		if ( offset !== undefined ) {

			console.warn( 'THREE.Vector4: offset has been removed from .fromBufferAttribute().' );

		}

		this.x = attribute.getX( index );
		this.y = attribute.getY( index );
		this.z = attribute.getZ( index );
		this.w = attribute.getW( index );

		return this;

	}

};

/**
 * @author mrdoob / http://mrdoob.com/
 */

function Color( r, g, b ) {

	if ( g === undefined && b === undefined ) {

		// r is THREE.Color, hex or string
		return this.set( r );

	}

	return this.setRGB( r, g, b );

}

Color.prototype = {

	constructor: Color,

	isColor: true,

	r: 1, g: 1, b: 1,

	set: function ( value ) {

		if ( value && value.isColor ) {

			this.copy( value );

		} else if ( typeof value === 'number' ) {

			this.setHex( value );

		} else if ( typeof value === 'string' ) {

			this.setStyle( value );

		}

		return this;

	},

	setScalar: function ( scalar ) {

		this.r = scalar;
		this.g = scalar;
		this.b = scalar;

		return this;

	},

	setHex: function ( hex ) {

		hex = Math.floor( hex );

		this.r = ( hex >> 16 & 255 ) / 255;
		this.g = ( hex >> 8 & 255 ) / 255;
		this.b = ( hex & 255 ) / 255;

		return this;

	},

	setRGB: function ( r, g, b ) {

		this.r = r;
		this.g = g;
		this.b = b;

		return this;

	},

	setHSL: function () {

		function hue2rgb( p, q, t ) {

			if ( t < 0 ) t += 1;
			if ( t > 1 ) t -= 1;
			if ( t < 1 / 6 ) return p + ( q - p ) * 6 * t;
			if ( t < 1 / 2 ) return q;
			if ( t < 2 / 3 ) return p + ( q - p ) * 6 * ( 2 / 3 - t );
			return p;

		}

		return function setHSL( h, s, l ) {

			// h,s,l ranges are in 0.0 - 1.0
			h = _Math.euclideanModulo( h, 1 );
			s = _Math.clamp( s, 0, 1 );
			l = _Math.clamp( l, 0, 1 );

			if ( s === 0 ) {

				this.r = this.g = this.b = l;

			} else {

				var p = l <= 0.5 ? l * ( 1 + s ) : l + s - ( l * s );
				var q = ( 2 * l ) - p;

				this.r = hue2rgb( q, p, h + 1 / 3 );
				this.g = hue2rgb( q, p, h );
				this.b = hue2rgb( q, p, h - 1 / 3 );

			}

			return this;

		};

	}(),

	setStyle: function ( style ) {

		function handleAlpha( string ) {

			if ( string === undefined ) return;

			if ( parseFloat( string ) < 1 ) {

				console.warn( 'THREE.Color: Alpha component of ' + style + ' will be ignored.' );

			}

		}


		var m;

		if ( m = /^((?:rgb|hsl)a?)\(\s*([^\)]*)\)/.exec( style ) ) {

			// rgb / hsl

			var color;
			var name = m[ 1 ];
			var components = m[ 2 ];

			switch ( name ) {

				case 'rgb':
				case 'rgba':

					if ( color = /^(\d+)\s*,\s*(\d+)\s*,\s*(\d+)\s*(,\s*([0-9]*\.?[0-9]+)\s*)?$/.exec( components ) ) {

						// rgb(255,0,0) rgba(255,0,0,0.5)
						this.r = Math.min( 255, parseInt( color[ 1 ], 10 ) ) / 255;
						this.g = Math.min( 255, parseInt( color[ 2 ], 10 ) ) / 255;
						this.b = Math.min( 255, parseInt( color[ 3 ], 10 ) ) / 255;

						handleAlpha( color[ 5 ] );

						return this;

					}

					if ( color = /^(\d+)\%\s*,\s*(\d+)\%\s*,\s*(\d+)\%\s*(,\s*([0-9]*\.?[0-9]+)\s*)?$/.exec( components ) ) {

						// rgb(100%,0%,0%) rgba(100%,0%,0%,0.5)
						this.r = Math.min( 100, parseInt( color[ 1 ], 10 ) ) / 100;
						this.g = Math.min( 100, parseInt( color[ 2 ], 10 ) ) / 100;
						this.b = Math.min( 100, parseInt( color[ 3 ], 10 ) ) / 100;

						handleAlpha( color[ 5 ] );

						return this;

					}

					break;

				case 'hsl':
				case 'hsla':

					if ( color = /^([0-9]*\.?[0-9]+)\s*,\s*(\d+)\%\s*,\s*(\d+)\%\s*(,\s*([0-9]*\.?[0-9]+)\s*)?$/.exec( components ) ) {

						// hsl(120,50%,50%) hsla(120,50%,50%,0.5)
						var h = parseFloat( color[ 1 ] ) / 360;
						var s = parseInt( color[ 2 ], 10 ) / 100;
						var l = parseInt( color[ 3 ], 10 ) / 100;

						handleAlpha( color[ 5 ] );

						return this.setHSL( h, s, l );

					}

					break;

			}

		} else if ( m = /^\#([A-Fa-f0-9]+)$/.exec( style ) ) {

			// hex color

			var hex = m[ 1 ];
			var size = hex.length;

			if ( size === 3 ) {

				// #ff0
				this.r = parseInt( hex.charAt( 0 ) + hex.charAt( 0 ), 16 ) / 255;
				this.g = parseInt( hex.charAt( 1 ) + hex.charAt( 1 ), 16 ) / 255;
				this.b = parseInt( hex.charAt( 2 ) + hex.charAt( 2 ), 16 ) / 255;

				return this;

			} else if ( size === 6 ) {

				// #ff0000
				this.r = parseInt( hex.charAt( 0 ) + hex.charAt( 1 ), 16 ) / 255;
				this.g = parseInt( hex.charAt( 2 ) + hex.charAt( 3 ), 16 ) / 255;
				this.b = parseInt( hex.charAt( 4 ) + hex.charAt( 5 ), 16 ) / 255;

				return this;

			}

		}

		if ( style && style.length > 0 ) {

			// color keywords
			var hex = ColorKeywords[ style ];

			if ( hex !== undefined ) {

				// red
				this.setHex( hex );

			} else {

				// unknown color
				console.warn( 'THREE.Color: Unknown color ' + style );

			}

		}

		return this;

	},

	clone: function () {

		return new this.constructor( this.r, this.g, this.b );

	},

	copy: function ( color ) {

		this.r = color.r;
		this.g = color.g;
		this.b = color.b;

		return this;

	},

	copyGammaToLinear: function ( color, gammaFactor ) {

		if ( gammaFactor === undefined ) gammaFactor = 2.0;

		this.r = Math.pow( color.r, gammaFactor );
		this.g = Math.pow( color.g, gammaFactor );
		this.b = Math.pow( color.b, gammaFactor );

		return this;

	},

	copyLinearToGamma: function ( color, gammaFactor ) {

		if ( gammaFactor === undefined ) gammaFactor = 2.0;

		var safeInverse = ( gammaFactor > 0 ) ? ( 1.0 / gammaFactor ) : 1.0;

		this.r = Math.pow( color.r, safeInverse );
		this.g = Math.pow( color.g, safeInverse );
		this.b = Math.pow( color.b, safeInverse );

		return this;

	},

	convertGammaToLinear: function () {

		var r = this.r, g = this.g, b = this.b;

		this.r = r * r;
		this.g = g * g;
		this.b = b * b;

		return this;

	},

	convertLinearToGamma: function () {

		this.r = Math.sqrt( this.r );
		this.g = Math.sqrt( this.g );
		this.b = Math.sqrt( this.b );

		return this;

	},

	getHex: function () {

		return ( this.r * 255 ) << 16 ^ ( this.g * 255 ) << 8 ^ ( this.b * 255 ) << 0;

	},

	getHexString: function () {

		return ( '000000' + this.getHex().toString( 16 ) ).slice( - 6 );

	},

	getHSL: function ( optionalTarget ) {

		// h,s,l ranges are in 0.0 - 1.0

		var hsl = optionalTarget || { h: 0, s: 0, l: 0 };

		var r = this.r, g = this.g, b = this.b;

		var max = Math.max( r, g, b );
		var min = Math.min( r, g, b );

		var hue, saturation;
		var lightness = ( min + max ) / 2.0;

		if ( min === max ) {

			hue = 0;
			saturation = 0;

		} else {

			var delta = max - min;

			saturation = lightness <= 0.5 ? delta / ( max + min ) : delta / ( 2 - max - min );

			switch ( max ) {

				case r: hue = ( g - b ) / delta + ( g < b ? 6 : 0 ); break;
				case g: hue = ( b - r ) / delta + 2; break;
				case b: hue = ( r - g ) / delta + 4; break;

			}

			hue /= 6;

		}

		hsl.h = hue;
		hsl.s = saturation;
		hsl.l = lightness;

		return hsl;

	},

	getStyle: function () {

		return 'rgb(' + ( ( this.r * 255 ) | 0 ) + ',' + ( ( this.g * 255 ) | 0 ) + ',' + ( ( this.b * 255 ) | 0 ) + ')';

	},

	offsetHSL: function ( h, s, l ) {

		var hsl = this.getHSL();

		hsl.h += h; hsl.s += s; hsl.l += l;

		this.setHSL( hsl.h, hsl.s, hsl.l );

		return this;

	},

	add: function ( color ) {

		this.r += color.r;
		this.g += color.g;
		this.b += color.b;

		return this;

	},

	addColors: function ( color1, color2 ) {

		this.r = color1.r + color2.r;
		this.g = color1.g + color2.g;
		this.b = color1.b + color2.b;

		return this;

	},

	addScalar: function ( s ) {

		this.r += s;
		this.g += s;
		this.b += s;

		return this;

	},

	sub: function( color ) {

		this.r = Math.max( 0, this.r - color.r );
		this.g = Math.max( 0, this.g - color.g );
		this.b = Math.max( 0, this.b - color.b );

		return this;

	},

	multiply: function ( color ) {

		this.r *= color.r;
		this.g *= color.g;
		this.b *= color.b;

		return this;

	},

	multiplyScalar: function ( s ) {

		this.r *= s;
		this.g *= s;
		this.b *= s;

		return this;

	},

	lerp: function ( color, alpha ) {

		this.r += ( color.r - this.r ) * alpha;
		this.g += ( color.g - this.g ) * alpha;
		this.b += ( color.b - this.b ) * alpha;

		return this;

	},

	equals: function ( c ) {

		return ( c.r === this.r ) && ( c.g === this.g ) && ( c.b === this.b );

	},

	fromArray: function ( array, offset ) {

		if ( offset === undefined ) offset = 0;

		this.r = array[ offset ];
		this.g = array[ offset + 1 ];
		this.b = array[ offset + 2 ];

		return this;

	},

	toArray: function ( array, offset ) {

		if ( array === undefined ) array = [];
		if ( offset === undefined ) offset = 0;

		array[ offset ] = this.r;
		array[ offset + 1 ] = this.g;
		array[ offset + 2 ] = this.b;

		return array;

	},

	toJSON: function () {

		return this.getHex();

	}

};

var ColorKeywords = { 'aliceblue': 0xF0F8FF, 'antiquewhite': 0xFAEBD7, 'aqua': 0x00FFFF, 'aquamarine': 0x7FFFD4, 'azure': 0xF0FFFF,
'beige': 0xF5F5DC, 'bisque': 0xFFE4C4, 'black': 0x000000, 'blanchedalmond': 0xFFEBCD, 'blue': 0x0000FF, 'blueviolet': 0x8A2BE2,
'brown': 0xA52A2A, 'burlywood': 0xDEB887, 'cadetblue': 0x5F9EA0, 'chartreuse': 0x7FFF00, 'chocolate': 0xD2691E, 'coral': 0xFF7F50,
'cornflowerblue': 0x6495ED, 'cornsilk': 0xFFF8DC, 'crimson': 0xDC143C, 'cyan': 0x00FFFF, 'darkblue': 0x00008B, 'darkcyan': 0x008B8B,
'darkgoldenrod': 0xB8860B, 'darkgray': 0xA9A9A9, 'darkgreen': 0x006400, 'darkgrey': 0xA9A9A9, 'darkkhaki': 0xBDB76B, 'darkmagenta': 0x8B008B,
'darkolivegreen': 0x556B2F, 'darkorange': 0xFF8C00, 'darkorchid': 0x9932CC, 'darkred': 0x8B0000, 'darksalmon': 0xE9967A, 'darkseagreen': 0x8FBC8F,
'darkslateblue': 0x483D8B, 'darkslategray': 0x2F4F4F, 'darkslategrey': 0x2F4F4F, 'darkturquoise': 0x00CED1, 'darkviolet': 0x9400D3,
'deeppink': 0xFF1493, 'deepskyblue': 0x00BFFF, 'dimgray': 0x696969, 'dimgrey': 0x696969, 'dodgerblue': 0x1E90FF, 'firebrick': 0xB22222,
'floralwhite': 0xFFFAF0, 'forestgreen': 0x228B22, 'fuchsia': 0xFF00FF, 'gainsboro': 0xDCDCDC, 'ghostwhite': 0xF8F8FF, 'gold': 0xFFD700,
'goldenrod': 0xDAA520, 'gray': 0x808080, 'green': 0x008000, 'greenyellow': 0xADFF2F, 'grey': 0x808080, 'honeydew': 0xF0FFF0, 'hotpink': 0xFF69B4,
'indianred': 0xCD5C5C, 'indigo': 0x4B0082, 'ivory': 0xFFFFF0, 'khaki': 0xF0E68C, 'lavender': 0xE6E6FA, 'lavenderblush': 0xFFF0F5, 'lawngreen': 0x7CFC00,
'lemonchiffon': 0xFFFACD, 'lightblue': 0xADD8E6, 'lightcoral': 0xF08080, 'lightcyan': 0xE0FFFF, 'lightgoldenrodyellow': 0xFAFAD2, 'lightgray': 0xD3D3D3,
'lightgreen': 0x90EE90, 'lightgrey': 0xD3D3D3, 'lightpink': 0xFFB6C1, 'lightsalmon': 0xFFA07A, 'lightseagreen': 0x20B2AA, 'lightskyblue': 0x87CEFA,
'lightslategray': 0x778899, 'lightslategrey': 0x778899, 'lightsteelblue': 0xB0C4DE, 'lightyellow': 0xFFFFE0, 'lime': 0x00FF00, 'limegreen': 0x32CD32,
'linen': 0xFAF0E6, 'magenta': 0xFF00FF, 'maroon': 0x800000, 'mediumaquamarine': 0x66CDAA, 'mediumblue': 0x0000CD, 'mediumorchid': 0xBA55D3,
'mediumpurple': 0x9370DB, 'mediumseagreen': 0x3CB371, 'mediumslateblue': 0x7B68EE, 'mediumspringgreen': 0x00FA9A, 'mediumturquoise': 0x48D1CC,
'mediumvioletred': 0xC71585, 'midnightblue': 0x191970, 'mintcream': 0xF5FFFA, 'mistyrose': 0xFFE4E1, 'moccasin': 0xFFE4B5, 'navajowhite': 0xFFDEAD,
'navy': 0x000080, 'oldlace': 0xFDF5E6, 'olive': 0x808000, 'olivedrab': 0x6B8E23, 'orange': 0xFFA500, 'orangered': 0xFF4500, 'orchid': 0xDA70D6,
'palegoldenrod': 0xEEE8AA, 'palegreen': 0x98FB98, 'paleturquoise': 0xAFEEEE, 'palevioletred': 0xDB7093, 'papayawhip': 0xFFEFD5, 'peachpuff': 0xFFDAB9,
'peru': 0xCD853F, 'pink': 0xFFC0CB, 'plum': 0xDDA0DD, 'powderblue': 0xB0E0E6, 'purple': 0x800080, 'red': 0xFF0000, 'rosybrown': 0xBC8F8F,
'royalblue': 0x4169E1, 'saddlebrown': 0x8B4513, 'salmon': 0xFA8072, 'sandybrown': 0xF4A460, 'seagreen': 0x2E8B57, 'seashell': 0xFFF5EE,
'sienna': 0xA0522D, 'silver': 0xC0C0C0, 'skyblue': 0x87CEEB, 'slateblue': 0x6A5ACD, 'slategray': 0x708090, 'slategrey': 0x708090, 'snow': 0xFFFAFA,
'springgreen': 0x00FF7F, 'steelblue': 0x4682B4, 'tan': 0xD2B48C, 'teal': 0x008080, 'thistle': 0xD8BFD8, 'tomato': 0xFF6347, 'turquoise': 0x40E0D0,
'violet': 0xEE82EE, 'wheat': 0xF5DEB3, 'white': 0xFFFFFF, 'whitesmoke': 0xF5F5F5, 'yellow': 0xFFFF00, 'yellowgreen': 0x9ACD32 };

/**
 * Uniforms library for shared webgl shaders
 */

var UniformsLib = {

	common: {

		diffuse: { value: new Color( 0xeeeeee ) },
		opacity: { value: 1.0 },

		map: { value: null },
		offsetRepeat: { value: new Vector4( 0, 0, 1, 1 ) },

		specularMap: { value: null },
		alphaMap: { value: null },

		envMap: { value: null },
		flipEnvMap: { value: - 1 },
		reflectivity: { value: 1.0 },
		refractionRatio: { value: 0.98 }

	},

	aomap: {

		aoMap: { value: null },
		aoMapIntensity: { value: 1 }

	},

	lightmap: {

		lightMap: { value: null },
		lightMapIntensity: { value: 1 }

	},

	emissivemap: {

		emissiveMap: { value: null }

	},

	bumpmap: {

		bumpMap: { value: null },
		bumpScale: { value: 1 }

	},

	normalmap: {

		normalMap: { value: null },
		normalScale: { value: new Vector2( 1, 1 ) }

	},

	displacementmap: {

		displacementMap: { value: null },
		displacementScale: { value: 1 },
		displacementBias: { value: 0 }

	},

	roughnessmap: {

		roughnessMap: { value: null }

	},

	metalnessmap: {

		metalnessMap: { value: null }

	},

	gradientmap: {

		gradientMap: { value: null }

	},

	fog: {

		fogDensity: { value: 0.00025 },
		fogNear: { value: 1 },
		fogFar: { value: 2000 },
		fogColor: { value: new Color( 0xffffff ) }

	},

	lights: {

		ambientLightColor: { value: [] },

		directionalLights: { value: [], properties: {
			direction: {},
			color: {},

			shadow: {},
			shadowBias: {},
			shadowRadius: {},
			shadowMapSize: {}
		} },

		directionalShadowMap: { value: [] },
		directionalShadowMatrix: { value: [] },

		spotLights: { value: [], properties: {
			color: {},
			position: {},
			direction: {},
			distance: {},
			coneCos: {},
			penumbraCos: {},
			decay: {},

			shadow: {},
			shadowBias: {},
			shadowRadius: {},
			shadowMapSize: {}
		} },

		spotShadowMap: { value: [] },
		spotShadowMatrix: { value: [] },

		pointLights: { value: [], properties: {
			color: {},
			position: {},
			decay: {},
			distance: {},

			shadow: {},
			shadowBias: {},
			shadowRadius: {},
			shadowMapSize: {}
		} },

		pointShadowMap: { value: [] },
		pointShadowMatrix: { value: [] },

		hemisphereLights: { value: [], properties: {
			direction: {},
			skyColor: {},
			groundColor: {}
		} },

		// TODO (abelnation): RectAreaLight BRDF data needs to be moved from example to main src
		rectAreaLights: { value: [], properties: {
			color: {},
			position: {},
			width: {},
			height: {}
		} }

	},

	points: {

		diffuse: { value: new Color( 0xeeeeee ) },
		opacity: { value: 1.0 },
		size: { value: 1.0 },
		scale: { value: 1.0 },
		map: { value: null },
		offsetRepeat: { value: new Vector4( 0, 0, 1, 1 ) }

	}

};

/**
 * @author alteredq / http://alteredqualia.com/
 * @author mrdoob / http://mrdoob.com/
 * @author mikael emtinger / http://gomo.se/
 */

var ShaderLib = {

	basic: {

		uniforms: UniformsUtils.merge( [
			UniformsLib.common,
			UniformsLib.aomap,
			UniformsLib.lightmap,
			UniformsLib.fog
		] ),

		vertexShader: ShaderChunk.meshbasic_vert,
		fragmentShader: ShaderChunk.meshbasic_frag

	},

	lambert: {

		uniforms: UniformsUtils.merge( [
			UniformsLib.common,
			UniformsLib.aomap,
			UniformsLib.lightmap,
			UniformsLib.emissivemap,
			UniformsLib.fog,
			UniformsLib.lights,
			{
				emissive: { value: new Color( 0x000000 ) }
			}
		] ),

		vertexShader: ShaderChunk.meshlambert_vert,
		fragmentShader: ShaderChunk.meshlambert_frag

	},

	phong: {

		uniforms: UniformsUtils.merge( [
			UniformsLib.common,
			UniformsLib.aomap,
			UniformsLib.lightmap,
			UniformsLib.emissivemap,
			UniformsLib.bumpmap,
			UniformsLib.normalmap,
			UniformsLib.displacementmap,
			UniformsLib.gradientmap,
			UniformsLib.fog,
			UniformsLib.lights,
			{
				emissive: { value: new Color( 0x000000 ) },
				specular: { value: new Color( 0x111111 ) },
				shininess: { value: 30 }
			}
		] ),

		vertexShader: ShaderChunk.meshphong_vert,
		fragmentShader: ShaderChunk.meshphong_frag

	},

	standard: {

		uniforms: UniformsUtils.merge( [
			UniformsLib.common,
			UniformsLib.aomap,
			UniformsLib.lightmap,
			UniformsLib.emissivemap,
			UniformsLib.bumpmap,
			UniformsLib.normalmap,
			UniformsLib.displacementmap,
			UniformsLib.roughnessmap,
			UniformsLib.metalnessmap,
			UniformsLib.fog,
			UniformsLib.lights,
			{
				emissive: { value: new Color( 0x000000 ) },
				roughness: { value: 0.5 },
				metalness: { value: 0 },
				envMapIntensity: { value: 1 } // temporary
			}
		] ),

		vertexShader: ShaderChunk.meshphysical_vert,
		fragmentShader: ShaderChunk.meshphysical_frag

	},

	points: {

		uniforms: UniformsUtils.merge( [
			UniformsLib.points,
			UniformsLib.fog
		] ),

		vertexShader: ShaderChunk.points_vert,
		fragmentShader: ShaderChunk.points_frag

	},

	dashed: {

		uniforms: UniformsUtils.merge( [
			UniformsLib.common,
			UniformsLib.fog,
			{
				scale: { value: 1 },
				dashSize: { value: 1 },
				totalSize: { value: 2 }
			}
		] ),

		vertexShader: ShaderChunk.linedashed_vert,
		fragmentShader: ShaderChunk.linedashed_frag

	},

	depth: {

		uniforms: UniformsUtils.merge( [
			UniformsLib.common,
			UniformsLib.displacementmap
		] ),

		vertexShader: ShaderChunk.depth_vert,
		fragmentShader: ShaderChunk.depth_frag

	},

	normal: {

		uniforms: UniformsUtils.merge( [
			UniformsLib.common,
			UniformsLib.bumpmap,
			UniformsLib.normalmap,
			UniformsLib.displacementmap,
			{
				opacity: { value: 1.0 }
			}
		] ),

		vertexShader: ShaderChunk.normal_vert,
		fragmentShader: ShaderChunk.normal_frag

	},

	/* -------------------------------------------------------------------------
	//	Cube map shader
	 ------------------------------------------------------------------------- */

	cube: {

		uniforms: {
			tCube: { value: null },
			tFlip: { value: - 1 },
			opacity: { value: 1.0 }
		},

		vertexShader: ShaderChunk.cube_vert,
		fragmentShader: ShaderChunk.cube_frag

	},

	/* -------------------------------------------------------------------------
	//	Cube map shader
	 ------------------------------------------------------------------------- */

	equirect: {

		uniforms: {
			tEquirect: { value: null },
			tFlip: { value: - 1 }
		},

		vertexShader: ShaderChunk.equirect_vert,
		fragmentShader: ShaderChunk.equirect_frag

	},

	distanceRGBA: {

		uniforms: {
			lightPos: { value: new Vector3() }
		},

		vertexShader: ShaderChunk.distanceRGBA_vert,
		fragmentShader: ShaderChunk.distanceRGBA_frag

	}

};

ShaderLib.physical = {

	uniforms: UniformsUtils.merge( [
		ShaderLib.standard.uniforms,
		{
			clearCoat: { value: 0 },
			clearCoatRoughness: { value: 0 }
		}
	] ),

	vertexShader: ShaderChunk.meshphysical_vert,
	fragmentShader: ShaderChunk.meshphysical_frag

};

/**
 * @author bhouston / http://clara.io
 */

function Box2( min, max ) {

	this.min = ( min !== undefined ) ? min : new Vector2( + Infinity, + Infinity );
	this.max = ( max !== undefined ) ? max : new Vector2( - Infinity, - Infinity );

}

Box2.prototype = {

	constructor: Box2,

	set: function ( min, max ) {

		this.min.copy( min );
		this.max.copy( max );

		return this;

	},

	setFromPoints: function ( points ) {

		this.makeEmpty();

		for ( var i = 0, il = points.length; i < il; i ++ ) {

			this.expandByPoint( points[ i ] );

		}

		return this;

	},

	setFromCenterAndSize: function () {

		var v1 = new Vector2();

		return function setFromCenterAndSize( center, size ) {

			var halfSize = v1.copy( size ).multiplyScalar( 0.5 );
			this.min.copy( center ).sub( halfSize );
			this.max.copy( center ).add( halfSize );

			return this;

		};

	}(),

	clone: function () {

		return new this.constructor().copy( this );

	},

	copy: function ( box ) {

		this.min.copy( box.min );
		this.max.copy( box.max );

		return this;

	},

	makeEmpty: function () {

		this.min.x = this.min.y = + Infinity;
		this.max.x = this.max.y = - Infinity;

		return this;

	},

	isEmpty: function () {

		// this is a more robust check for empty than ( volume <= 0 ) because volume can get positive with two negative axes

		return ( this.max.x < this.min.x ) || ( this.max.y < this.min.y );

	},

	getCenter: function ( optionalTarget ) {

		var result = optionalTarget || new Vector2();
		return this.isEmpty() ? result.set( 0, 0 ) : result.addVectors( this.min, this.max ).multiplyScalar( 0.5 );

	},

	getSize: function ( optionalTarget ) {

		var result = optionalTarget || new Vector2();
		return this.isEmpty() ? result.set( 0, 0 ) : result.subVectors( this.max, this.min );

	},

	expandByPoint: function ( point ) {

		this.min.min( point );
		this.max.max( point );

		return this;

	},

	expandByVector: function ( vector ) {

		this.min.sub( vector );
		this.max.add( vector );

		return this;

	},

	expandByScalar: function ( scalar ) {

		this.min.addScalar( - scalar );
		this.max.addScalar( scalar );

		return this;

	},

	containsPoint: function ( point ) {

		return point.x < this.min.x || point.x > this.max.x ||
			point.y < this.min.y || point.y > this.max.y ? false : true;

	},

	containsBox: function ( box ) {

		return this.min.x <= box.min.x && box.max.x <= this.max.x &&
			this.min.y <= box.min.y && box.max.y <= this.max.y;

	},

	getParameter: function ( point, optionalTarget ) {

		// This can potentially have a divide by zero if the box
		// has a size dimension of 0.

		var result = optionalTarget || new Vector2();

		return result.set(
			( point.x - this.min.x ) / ( this.max.x - this.min.x ),
			( point.y - this.min.y ) / ( this.max.y - this.min.y )
		);

	},

	intersectsBox: function ( box ) {

		// using 6 splitting planes to rule out intersections.
		return box.max.x < this.min.x || box.min.x > this.max.x ||
			box.max.y < this.min.y || box.min.y > this.max.y ? false : true;

	},

	clampPoint: function ( point, optionalTarget ) {

		var result = optionalTarget || new Vector2();
		return result.copy( point ).clamp( this.min, this.max );

	},

	distanceToPoint: function () {

		var v1 = new Vector2();

		return function distanceToPoint( point ) {

			var clampedPoint = v1.copy( point ).clamp( this.min, this.max );
			return clampedPoint.sub( point ).length();

		};

	}(),

	intersect: function ( box ) {

		this.min.max( box.min );
		this.max.min( box.max );

		return this;

	},

	union: function ( box ) {

		this.min.min( box.min );
		this.max.max( box.max );

		return this;

	},

	translate: function ( offset ) {

		this.min.add( offset );
		this.max.add( offset );

		return this;

	},

	equals: function ( box ) {

		return box.min.equals( this.min ) && box.max.equals( this.max );

	}

};

/**
 * @author mikael emtinger / http://gomo.se/
 * @author alteredq / http://alteredqualia.com/
 */

function LensFlarePlugin( renderer, flares ) {

	var gl = renderer.context;
	var state = renderer.state;

	var vertexBuffer, elementBuffer;
	var shader, program, attributes, uniforms;

	var tempTexture, occlusionTexture;

	function init() {

		var vertices = new Float32Array( [
			- 1, - 1,  0, 0,
			 1, - 1,  1, 0,
			 1,  1,  1, 1,
			- 1,  1,  0, 1
		] );

		var faces = new Uint16Array( [
			0, 1, 2,
			0, 2, 3
		] );

		// buffers

		vertexBuffer     = gl.createBuffer();
		elementBuffer    = gl.createBuffer();

		gl.bindBuffer( gl.ARRAY_BUFFER, vertexBuffer );
		gl.bufferData( gl.ARRAY_BUFFER, vertices, gl.STATIC_DRAW );

		gl.bindBuffer( gl.ELEMENT_ARRAY_BUFFER, elementBuffer );
		gl.bufferData( gl.ELEMENT_ARRAY_BUFFER, faces, gl.STATIC_DRAW );

		// textures

		tempTexture      = gl.createTexture();
		occlusionTexture = gl.createTexture();

		state.bindTexture( gl.TEXTURE_2D, tempTexture );
		gl.texImage2D( gl.TEXTURE_2D, 0, gl.RGB, 16, 16, 0, gl.RGB, gl.UNSIGNED_BYTE, null );
		gl.texParameteri( gl.TEXTURE_2D, gl.TEXTURE_WRAP_S, gl.CLAMP_TO_EDGE );
		gl.texParameteri( gl.TEXTURE_2D, gl.TEXTURE_WRAP_T, gl.CLAMP_TO_EDGE );
		gl.texParameteri( gl.TEXTURE_2D, gl.TEXTURE_MAG_FILTER, gl.NEAREST );
		gl.texParameteri( gl.TEXTURE_2D, gl.TEXTURE_MIN_FILTER, gl.NEAREST );

		state.bindTexture( gl.TEXTURE_2D, occlusionTexture );
		gl.texImage2D( gl.TEXTURE_2D, 0, gl.RGBA, 16, 16, 0, gl.RGBA, gl.UNSIGNED_BYTE, null );
		gl.texParameteri( gl.TEXTURE_2D, gl.TEXTURE_WRAP_S, gl.CLAMP_TO_EDGE );
		gl.texParameteri( gl.TEXTURE_2D, gl.TEXTURE_WRAP_T, gl.CLAMP_TO_EDGE );
		gl.texParameteri( gl.TEXTURE_2D, gl.TEXTURE_MAG_FILTER, gl.NEAREST );
		gl.texParameteri( gl.TEXTURE_2D, gl.TEXTURE_MIN_FILTER, gl.NEAREST );

		shader = {

			vertexShader: [

				"uniform lowp int renderType;",

				"uniform vec3 screenPosition;",
				"uniform vec2 scale;",
				"uniform float rotation;",

				"uniform sampler2D occlusionMap;",

				"attribute vec2 position;",
				"attribute vec2 uv;",

				"varying vec2 vUV;",
				"varying float vVisibility;",

				"void main() {",

					"vUV = uv;",

					"vec2 pos = position;",

					"if ( renderType == 2 ) {",

						"vec4 visibility = texture2D( occlusionMap, vec2( 0.1, 0.1 ) );",
						"visibility += texture2D( occlusionMap, vec2( 0.5, 0.1 ) );",
						"visibility += texture2D( occlusionMap, vec2( 0.9, 0.1 ) );",
						"visibility += texture2D( occlusionMap, vec2( 0.9, 0.5 ) );",
						"visibility += texture2D( occlusionMap, vec2( 0.9, 0.9 ) );",
						"visibility += texture2D( occlusionMap, vec2( 0.5, 0.9 ) );",
						"visibility += texture2D( occlusionMap, vec2( 0.1, 0.9 ) );",
						"visibility += texture2D( occlusionMap, vec2( 0.1, 0.5 ) );",
						"visibility += texture2D( occlusionMap, vec2( 0.5, 0.5 ) );",

						"vVisibility =        visibility.r / 9.0;",
						"vVisibility *= 1.0 - visibility.g / 9.0;",
						"vVisibility *=       visibility.b / 9.0;",
						"vVisibility *= 1.0 - visibility.a / 9.0;",

						"pos.x = cos( rotation ) * position.x - sin( rotation ) * position.y;",
						"pos.y = sin( rotation ) * position.x + cos( rotation ) * position.y;",

					"}",

					"gl_Position = vec4( ( pos * scale + screenPosition.xy ).xy, screenPosition.z, 1.0 );",

				"}"

			].join( "\n" ),

			fragmentShader: [

				"uniform lowp int renderType;",

				"uniform sampler2D map;",
				"uniform float opacity;",
				"uniform vec3 color;",

				"varying vec2 vUV;",
				"varying float vVisibility;",

				"void main() {",

					// pink square

					"if ( renderType == 0 ) {",

						"gl_FragColor = vec4( 1.0, 0.0, 1.0, 0.0 );",

					// restore

					"} else if ( renderType == 1 ) {",

						"gl_FragColor = texture2D( map, vUV );",

					// flare

					"} else {",

						"vec4 texture = texture2D( map, vUV );",
						"texture.a *= opacity * vVisibility;",
						"gl_FragColor = texture;",
						"gl_FragColor.rgb *= color;",

					"}",

				"}"

			].join( "\n" )

		};

		program = createProgram( shader );

		attributes = {
			vertex: gl.getAttribLocation ( program, "position" ),
			uv:     gl.getAttribLocation ( program, "uv" )
		};

		uniforms = {
			renderType:     gl.getUniformLocation( program, "renderType" ),
			map:            gl.getUniformLocation( program, "map" ),
			occlusionMap:   gl.getUniformLocation( program, "occlusionMap" ),
			opacity:        gl.getUniformLocation( program, "opacity" ),
			color:          gl.getUniformLocation( program, "color" ),
			scale:          gl.getUniformLocation( program, "scale" ),
			rotation:       gl.getUniformLocation( program, "rotation" ),
			screenPosition: gl.getUniformLocation( program, "screenPosition" )
		};

	}

	/*
	 * Render lens flares
	 * Method: renders 16x16 0xff00ff-colored points scattered over the light source area,
	 *         reads these back and calculates occlusion.
	 */

	this.render = function ( scene, camera, viewport ) {

		if ( flares.length === 0 ) return;

		var tempPosition = new Vector3();

		var invAspect = viewport.w / viewport.z,
			halfViewportWidth = viewport.z * 0.5,
			halfViewportHeight = viewport.w * 0.5;

		var size = 16 / viewport.w,
			scale = new Vector2( size * invAspect, size );

		var screenPosition = new Vector3( 1, 1, 0 ),
			screenPositionPixels = new Vector2( 1, 1 );

		var validArea = new Box2();

		validArea.min.set( viewport.x, viewport.y );
		validArea.max.set( viewport.x + ( viewport.z - 16 ), viewport.y + ( viewport.w - 16 ) );

		if ( program === undefined ) {

			init();

		}

		gl.useProgram( program );

		state.initAttributes();
		state.enableAttribute( attributes.vertex );
		state.enableAttribute( attributes.uv );
		state.disableUnusedAttributes();

		// loop through all lens flares to update their occlusion and positions
		// setup gl and common used attribs/uniforms

		gl.uniform1i( uniforms.occlusionMap, 0 );
		gl.uniform1i( uniforms.map, 1 );

		gl.bindBuffer( gl.ARRAY_BUFFER, vertexBuffer );
		gl.vertexAttribPointer( attributes.vertex, 2, gl.FLOAT, false, 2 * 8, 0 );
		gl.vertexAttribPointer( attributes.uv, 2, gl.FLOAT, false, 2 * 8, 8 );

		gl.bindBuffer( gl.ELEMENT_ARRAY_BUFFER, elementBuffer );

		state.disable( gl.CULL_FACE );
		state.setDepthWrite( false );

		for ( var i = 0, l = flares.length; i < l; i ++ ) {

			size = 16 / viewport.w;
			scale.set( size * invAspect, size );

			// calc object screen position

			var flare = flares[ i ];

			tempPosition.set( flare.matrixWorld.elements[ 12 ], flare.matrixWorld.elements[ 13 ], flare.matrixWorld.elements[ 14 ] );

			tempPosition.applyMatrix4( camera.matrixWorldInverse );
			tempPosition.applyMatrix4( camera.projectionMatrix );

			// setup arrays for gl programs

			screenPosition.copy( tempPosition );

			// horizontal and vertical coordinate of the lower left corner of the pixels to copy

			screenPositionPixels.x = viewport.x + ( screenPosition.x * halfViewportWidth ) + halfViewportWidth - 8;
			screenPositionPixels.y = viewport.y + ( screenPosition.y * halfViewportHeight ) + halfViewportHeight - 8;

			// screen cull

			if ( validArea.containsPoint( screenPositionPixels ) === true ) {

				// save current RGB to temp texture

				state.activeTexture( gl.TEXTURE0 );
				state.bindTexture( gl.TEXTURE_2D, null );
				state.activeTexture( gl.TEXTURE1 );
				state.bindTexture( gl.TEXTURE_2D, tempTexture );
				gl.copyTexImage2D( gl.TEXTURE_2D, 0, gl.RGB, screenPositionPixels.x, screenPositionPixels.y, 16, 16, 0 );


				// render pink quad

				gl.uniform1i( uniforms.renderType, 0 );
				gl.uniform2f( uniforms.scale, scale.x, scale.y );
				gl.uniform3f( uniforms.screenPosition, screenPosition.x, screenPosition.y, screenPosition.z );

				state.disable( gl.BLEND );
				state.enable( gl.DEPTH_TEST );

				gl.drawElements( gl.TRIANGLES, 6, gl.UNSIGNED_SHORT, 0 );


				// copy result to occlusionMap

				state.activeTexture( gl.TEXTURE0 );
				state.bindTexture( gl.TEXTURE_2D, occlusionTexture );
				gl.copyTexImage2D( gl.TEXTURE_2D, 0, gl.RGBA, screenPositionPixels.x, screenPositionPixels.y, 16, 16, 0 );


				// restore graphics

				gl.uniform1i( uniforms.renderType, 1 );
				state.disable( gl.DEPTH_TEST );

				state.activeTexture( gl.TEXTURE1 );
				state.bindTexture( gl.TEXTURE_2D, tempTexture );
				gl.drawElements( gl.TRIANGLES, 6, gl.UNSIGNED_SHORT, 0 );


				// update object positions

				flare.positionScreen.copy( screenPosition );

				if ( flare.customUpdateCallback ) {

					flare.customUpdateCallback( flare );

				} else {

					flare.updateLensFlares();

				}

				// render flares

				gl.uniform1i( uniforms.renderType, 2 );
				state.enable( gl.BLEND );

				for ( var j = 0, jl = flare.lensFlares.length; j < jl; j ++ ) {

					var sprite = flare.lensFlares[ j ];

					if ( sprite.opacity > 0.001 && sprite.scale > 0.001 ) {

						screenPosition.x = sprite.x;
						screenPosition.y = sprite.y;
						screenPosition.z = sprite.z;

						size = sprite.size * sprite.scale / viewport.w;

						scale.x = size * invAspect;
						scale.y = size;

						gl.uniform3f( uniforms.screenPosition, screenPosition.x, screenPosition.y, screenPosition.z );
						gl.uniform2f( uniforms.scale, scale.x, scale.y );
						gl.uniform1f( uniforms.rotation, sprite.rotation );

						gl.uniform1f( uniforms.opacity, sprite.opacity );
						gl.uniform3f( uniforms.color, sprite.color.r, sprite.color.g, sprite.color.b );

						state.setBlending( sprite.blending, sprite.blendEquation, sprite.blendSrc, sprite.blendDst );
						renderer.setTexture2D( sprite.texture, 1 );

						gl.drawElements( gl.TRIANGLES, 6, gl.UNSIGNED_SHORT, 0 );

					}

				}

			}

		}

		// restore gl

		state.enable( gl.CULL_FACE );
		state.enable( gl.DEPTH_TEST );
		state.setDepthWrite( true );

		renderer.resetGLState();

	};

	function createProgram( shader ) {

		var program = gl.createProgram();

		var fragmentShader = gl.createShader( gl.FRAGMENT_SHADER );
		var vertexShader = gl.createShader( gl.VERTEX_SHADER );

		var prefix = "precision " + renderer.getPrecision() + " float;\n";

		gl.shaderSource( fragmentShader, prefix + shader.fragmentShader );
		gl.shaderSource( vertexShader, prefix + shader.vertexShader );

		gl.compileShader( fragmentShader );
		gl.compileShader( vertexShader );

		gl.attachShader( program, fragmentShader );
		gl.attachShader( program, vertexShader );

		gl.linkProgram( program );

		return program;

	}

}

/**
 * @author mikael emtinger / http://gomo.se/
 * @author alteredq / http://alteredqualia.com/
 */

function SpritePlugin( renderer, sprites ) {

	var gl = renderer.context;
	var state = renderer.state;

	var vertexBuffer, elementBuffer;
	var program, attributes, uniforms;

	var texture;

	// decompose matrixWorld

	var spritePosition = new Vector3();
	var spriteRotation = new Quaternion();
	var spriteScale = new Vector3();

	function init() {

		var vertices = new Float32Array( [
			- 0.5, - 0.5,  0, 0,
			  0.5, - 0.5,  1, 0,
			  0.5,   0.5,  1, 1,
			- 0.5,   0.5,  0, 1
		] );

		var faces = new Uint16Array( [
			0, 1, 2,
			0, 2, 3
		] );

		vertexBuffer  = gl.createBuffer();
		elementBuffer = gl.createBuffer();

		gl.bindBuffer( gl.ARRAY_BUFFER, vertexBuffer );
		gl.bufferData( gl.ARRAY_BUFFER, vertices, gl.STATIC_DRAW );

		gl.bindBuffer( gl.ELEMENT_ARRAY_BUFFER, elementBuffer );
		gl.bufferData( gl.ELEMENT_ARRAY_BUFFER, faces, gl.STATIC_DRAW );

		program = createProgram();

		attributes = {
			position:			gl.getAttribLocation ( program, 'position' ),
			uv:					gl.getAttribLocation ( program, 'uv' )
		};

		uniforms = {
			uvOffset:			gl.getUniformLocation( program, 'uvOffset' ),
			uvScale:			gl.getUniformLocation( program, 'uvScale' ),

			rotation:			gl.getUniformLocation( program, 'rotation' ),
			scale:				gl.getUniformLocation( program, 'scale' ),

			color:				gl.getUniformLocation( program, 'color' ),
			map:				gl.getUniformLocation( program, 'map' ),
			opacity:			gl.getUniformLocation( program, 'opacity' ),

			modelViewMatrix: 	gl.getUniformLocation( program, 'modelViewMatrix' ),
			projectionMatrix:	gl.getUniformLocation( program, 'projectionMatrix' ),

			fogType:			gl.getUniformLocation( program, 'fogType' ),
			fogDensity:			gl.getUniformLocation( program, 'fogDensity' ),
			fogNear:			gl.getUniformLocation( program, 'fogNear' ),
			fogFar:				gl.getUniformLocation( program, 'fogFar' ),
			fogColor:			gl.getUniformLocation( program, 'fogColor' ),

			alphaTest:			gl.getUniformLocation( program, 'alphaTest' )
		};

		var canvas = document.createElementNS( 'http://www.w3.org/1999/xhtml', 'canvas' );
		canvas.width = 8;
		canvas.height = 8;

		var context = canvas.getContext( '2d' );
		context.fillStyle = 'white';
		context.fillRect( 0, 0, 8, 8 );

		texture = new Texture( canvas );
		texture.needsUpdate = true;

	}

	this.render = function ( scene, camera ) {

		if ( sprites.length === 0 ) return;

		// setup gl

		if ( program === undefined ) {

			init();

		}

		gl.useProgram( program );

		state.initAttributes();
		state.enableAttribute( attributes.position );
		state.enableAttribute( attributes.uv );
		state.disableUnusedAttributes();

		state.disable( gl.CULL_FACE );
		state.enable( gl.BLEND );

		gl.bindBuffer( gl.ARRAY_BUFFER, vertexBuffer );
		gl.vertexAttribPointer( attributes.position, 2, gl.FLOAT, false, 2 * 8, 0 );
		gl.vertexAttribPointer( attributes.uv, 2, gl.FLOAT, false, 2 * 8, 8 );

		gl.bindBuffer( gl.ELEMENT_ARRAY_BUFFER, elementBuffer );

		gl.uniformMatrix4fv( uniforms.projectionMatrix, false, camera.projectionMatrix.elements );

		state.activeTexture( gl.TEXTURE0 );
		gl.uniform1i( uniforms.map, 0 );

		var oldFogType = 0;
		var sceneFogType = 0;
		var fog = scene.fog;

		if ( fog ) {

			gl.uniform3f( uniforms.fogColor, fog.color.r, fog.color.g, fog.color.b );

			if ( fog.isFog ) {

				gl.uniform1f( uniforms.fogNear, fog.near );
				gl.uniform1f( uniforms.fogFar, fog.far );

				gl.uniform1i( uniforms.fogType, 1 );
				oldFogType = 1;
				sceneFogType = 1;

			} else if ( fog.isFogExp2 ) {

				gl.uniform1f( uniforms.fogDensity, fog.density );

				gl.uniform1i( uniforms.fogType, 2 );
				oldFogType = 2;
				sceneFogType = 2;

			}

		} else {

			gl.uniform1i( uniforms.fogType, 0 );
			oldFogType = 0;
			sceneFogType = 0;

		}


		// update positions and sort

		for ( var i = 0, l = sprites.length; i < l; i ++ ) {

			var sprite = sprites[ i ];

			sprite.modelViewMatrix.multiplyMatrices( camera.matrixWorldInverse, sprite.matrixWorld );
			sprite.z = - sprite.modelViewMatrix.elements[ 14 ];

		}

		sprites.sort( painterSortStable );

		// render all sprites

		var scale = [];

		for ( var i = 0, l = sprites.length; i < l; i ++ ) {

			var sprite = sprites[ i ];
			var material = sprite.material;

			if ( material.visible === false ) continue;

			gl.uniform1f( uniforms.alphaTest, material.alphaTest );
			gl.uniformMatrix4fv( uniforms.modelViewMatrix, false, sprite.modelViewMatrix.elements );

			sprite.matrixWorld.decompose( spritePosition, spriteRotation, spriteScale );

			scale[ 0 ] = spriteScale.x;
			scale[ 1 ] = spriteScale.y;

			var fogType = 0;

			if ( scene.fog && material.fog ) {

				fogType = sceneFogType;

			}

			if ( oldFogType !== fogType ) {

				gl.uniform1i( uniforms.fogType, fogType );
				oldFogType = fogType;

			}

			if ( material.map !== null ) {

				gl.uniform2f( uniforms.uvOffset, material.map.offset.x, material.map.offset.y );
				gl.uniform2f( uniforms.uvScale, material.map.repeat.x, material.map.repeat.y );

			} else {

				gl.uniform2f( uniforms.uvOffset, 0, 0 );
				gl.uniform2f( uniforms.uvScale, 1, 1 );

			}

			gl.uniform1f( uniforms.opacity, material.opacity );
			gl.uniform3f( uniforms.color, material.color.r, material.color.g, material.color.b );

			gl.uniform1f( uniforms.rotation, material.rotation );
			gl.uniform2fv( uniforms.scale, scale );

			state.setBlending( material.blending, material.blendEquation, material.blendSrc, material.blendDst );
			state.setDepthTest( material.depthTest );
			state.setDepthWrite( material.depthWrite );

			if ( material.map ) {

				renderer.setTexture2D( material.map, 0 );

			} else {

				renderer.setTexture2D( texture, 0 );

			}

			gl.drawElements( gl.TRIANGLES, 6, gl.UNSIGNED_SHORT, 0 );

		}

		// restore gl

		state.enable( gl.CULL_FACE );

		renderer.resetGLState();

	};

	function createProgram() {

		var program = gl.createProgram();

		var vertexShader = gl.createShader( gl.VERTEX_SHADER );
		var fragmentShader = gl.createShader( gl.FRAGMENT_SHADER );

		gl.shaderSource( vertexShader, [

			'precision ' + renderer.getPrecision() + ' float;',

			'uniform mat4 modelViewMatrix;',
			'uniform mat4 projectionMatrix;',
			'uniform float rotation;',
			'uniform vec2 scale;',
			'uniform vec2 uvOffset;',
			'uniform vec2 uvScale;',

			'attribute vec2 position;',
			'attribute vec2 uv;',

			'varying vec2 vUV;',

			'void main() {',

				'vUV = uvOffset + uv * uvScale;',

				'vec2 alignedPosition = position * scale;',

				'vec2 rotatedPosition;',
				'rotatedPosition.x = cos( rotation ) * alignedPosition.x - sin( rotation ) * alignedPosition.y;',
				'rotatedPosition.y = sin( rotation ) * alignedPosition.x + cos( rotation ) * alignedPosition.y;',

				'vec4 finalPosition;',

				'finalPosition = modelViewMatrix * vec4( 0.0, 0.0, 0.0, 1.0 );',
				'finalPosition.xy += rotatedPosition;',
				'finalPosition = projectionMatrix * finalPosition;',

				'gl_Position = finalPosition;',

			'}'

		].join( '\n' ) );

		gl.shaderSource( fragmentShader, [

			'precision ' + renderer.getPrecision() + ' float;',

			'uniform vec3 color;',
			'uniform sampler2D map;',
			'uniform float opacity;',

			'uniform int fogType;',
			'uniform vec3 fogColor;',
			'uniform float fogDensity;',
			'uniform float fogNear;',
			'uniform float fogFar;',
			'uniform float alphaTest;',

			'varying vec2 vUV;',

			'void main() {',

				'vec4 texture = texture2D( map, vUV );',

				'if ( texture.a < alphaTest ) discard;',

				'gl_FragColor = vec4( color * texture.xyz, texture.a * opacity );',

				'if ( fogType > 0 ) {',

					'float depth = gl_FragCoord.z / gl_FragCoord.w;',
					'float fogFactor = 0.0;',

					'if ( fogType == 1 ) {',

						'fogFactor = smoothstep( fogNear, fogFar, depth );',

					'} else {',

						'const float LOG2 = 1.442695;',
						'fogFactor = exp2( - fogDensity * fogDensity * depth * depth * LOG2 );',
						'fogFactor = 1.0 - clamp( fogFactor, 0.0, 1.0 );',

					'}',

					'gl_FragColor = mix( gl_FragColor, vec4( fogColor, gl_FragColor.w ), fogFactor );',

				'}',

			'}'

		].join( '\n' ) );

		gl.compileShader( vertexShader );
		gl.compileShader( fragmentShader );

		gl.attachShader( program, vertexShader );
		gl.attachShader( program, fragmentShader );

		gl.linkProgram( program );

		return program;

	}

	function painterSortStable( a, b ) {

		if ( a.renderOrder !== b.renderOrder ) {

			return a.renderOrder - b.renderOrder;

		} else if ( a.z !== b.z ) {

			return b.z - a.z;

		} else {

			return b.id - a.id;

		}

	}

}

/**
 * @author szimek / https://github.com/szimek/
 * @author alteredq / http://alteredqualia.com/
 * @author Marius Kintel / https://github.com/kintel
 */

/*
 In options, we can specify:
 * Texture parameters for an auto-generated target texture
 * depthBuffer/stencilBuffer: Booleans to indicate if we should generate these buffers
*/
function WebGLRenderTarget( width, height, options ) {

	this.uuid = _Math.generateUUID();

	this.width = width;
	this.height = height;

	this.scissor = new Vector4( 0, 0, width, height );
	this.scissorTest = false;

	this.viewport = new Vector4( 0, 0, width, height );

	options = options || {};

	if ( options.minFilter === undefined ) options.minFilter = LinearFilter;

	this.texture = new Texture( undefined, undefined, options.wrapS, options.wrapT, options.magFilter, options.minFilter, options.format, options.type, options.anisotropy, options.encoding );

	this.depthBuffer = options.depthBuffer !== undefined ? options.depthBuffer : true;
	this.stencilBuffer = options.stencilBuffer !== undefined ? options.stencilBuffer : true;
	this.depthTexture = options.depthTexture !== undefined ? options.depthTexture : null;

}

WebGLRenderTarget.prototype = {

	constructor: WebGLRenderTarget,

	isWebGLRenderTarget: true,

	setSize: function ( width, height ) {

		if ( this.width !== width || this.height !== height ) {

			this.width = width;
			this.height = height;

			this.dispose();

		}

		this.viewport.set( 0, 0, width, height );
		this.scissor.set( 0, 0, width, height );

	},

	clone: function () {

		return new this.constructor().copy( this );

	},

	copy: function ( source ) {

		this.width = source.width;
		this.height = source.height;

		this.viewport.copy( source.viewport );

		this.texture = source.texture.clone();

		this.depthBuffer = source.depthBuffer;
		this.stencilBuffer = source.stencilBuffer;
		this.depthTexture = source.depthTexture;

		return this;

	},

	dispose: function () {

		this.dispatchEvent( { type: 'dispose' } );

	}

};

Object.assign( WebGLRenderTarget.prototype, EventDispatcher.prototype );

/**
 * @author mrdoob / http://mrdoob.com/
 * @author alteredq / http://alteredqualia.com/
 */

var materialId = 0;

function Material() {

	Object.defineProperty( this, 'id', { value: materialId ++ } );

	this.uuid = _Math.generateUUID();

	this.name = '';
	this.type = 'Material';

	this.fog = true;
	this.lights = true;

	this.blending = NormalBlending;
	this.side = FrontSide;
	this.shading = SmoothShading; // THREE.FlatShading, THREE.SmoothShading
	this.vertexColors = NoColors; // THREE.NoColors, THREE.VertexColors, THREE.FaceColors

	this.opacity = 1;
	this.transparent = false;

	this.blendSrc = SrcAlphaFactor;
	this.blendDst = OneMinusSrcAlphaFactor;
	this.blendEquation = AddEquation;
	this.blendSrcAlpha = null;
	this.blendDstAlpha = null;
	this.blendEquationAlpha = null;

	this.depthFunc = LessEqualDepth;
	this.depthTest = true;
	this.depthWrite = true;

	this.clippingPlanes = null;
	this.clipIntersection = false;
	this.clipShadows = false;

	this.colorWrite = true;

	this.precision = null; // override the renderer's default precision for this material

	this.polygonOffset = false;
	this.polygonOffsetFactor = 0;
	this.polygonOffsetUnits = 0;

	this.alphaTest = 0;
	this.premultipliedAlpha = false;

	this.overdraw = 0; // Overdrawn pixels (typically between 0 and 1) for fixing antialiasing gaps in CanvasRenderer

	this.visible = true;

	this._needsUpdate = true;

}

Material.prototype = {

	constructor: Material,

	isMaterial: true,

	get needsUpdate() {

		return this._needsUpdate;

	},

	set needsUpdate( value ) {

		if ( value === true ) this.update();
		this._needsUpdate = value;

	},

	setValues: function ( values ) {

		if ( values === undefined ) return;

		for ( var key in values ) {

			var newValue = values[ key ];

			if ( newValue === undefined ) {

				console.warn( "THREE.Material: '" + key + "' parameter is undefined." );
				continue;

			}

			var currentValue = this[ key ];

			if ( currentValue === undefined ) {

				console.warn( "THREE." + this.type + ": '" + key + "' is not a property of this material." );
				continue;

			}

			if ( currentValue && currentValue.isColor ) {

				currentValue.set( newValue );

			} else if ( ( currentValue && currentValue.isVector3 ) && ( newValue && newValue.isVector3 ) ) {

				currentValue.copy( newValue );

			} else if ( key === 'overdraw' ) {

				// ensure overdraw is backwards-compatible with legacy boolean type
				this[ key ] = Number( newValue );

			} else {

				this[ key ] = newValue;

			}

		}

	},

	toJSON: function ( meta ) {

		var isRoot = meta === undefined;

		if ( isRoot ) {

			meta = {
				textures: {},
				images: {}
			};

		}

		var data = {
			metadata: {
				version: 4.4,
				type: 'Material',
				generator: 'Material.toJSON'
			}
		};

		// standard Material serialization
		data.uuid = this.uuid;
		data.type = this.type;

		if ( this.name !== '' ) data.name = this.name;

		if ( this.color && this.color.isColor ) data.color = this.color.getHex();

		if ( this.roughness !== undefined ) data.roughness = this.roughness;
		if ( this.metalness !== undefined ) data.metalness = this.metalness;

		if ( this.emissive && this.emissive.isColor ) data.emissive = this.emissive.getHex();
		if ( this.specular && this.specular.isColor ) data.specular = this.specular.getHex();
		if ( this.shininess !== undefined ) data.shininess = this.shininess;
		if ( this.clearCoat !== undefined ) data.clearCoat = this.clearCoat;
		if ( this.clearCoatRoughness !== undefined ) data.clearCoatRoughness = this.clearCoatRoughness;

		if ( this.map && this.map.isTexture ) data.map = this.map.toJSON( meta ).uuid;
		if ( this.alphaMap && this.alphaMap.isTexture ) data.alphaMap = this.alphaMap.toJSON( meta ).uuid;
		if ( this.lightMap && this.lightMap.isTexture ) data.lightMap = this.lightMap.toJSON( meta ).uuid;
		if ( this.bumpMap && this.bumpMap.isTexture ) {

			data.bumpMap = this.bumpMap.toJSON( meta ).uuid;
			data.bumpScale = this.bumpScale;

		}
		if ( this.normalMap && this.normalMap.isTexture ) {

			data.normalMap = this.normalMap.toJSON( meta ).uuid;
			data.normalScale = this.normalScale.toArray();

		}
		if ( this.displacementMap && this.displacementMap.isTexture ) {

			data.displacementMap = this.displacementMap.toJSON( meta ).uuid;
			data.displacementScale = this.displacementScale;
			data.displacementBias = this.displacementBias;

		}
		if ( this.roughnessMap && this.roughnessMap.isTexture ) data.roughnessMap = this.roughnessMap.toJSON( meta ).uuid;
		if ( this.metalnessMap && this.metalnessMap.isTexture ) data.metalnessMap = this.metalnessMap.toJSON( meta ).uuid;

		if ( this.emissiveMap && this.emissiveMap.isTexture ) data.emissiveMap = this.emissiveMap.toJSON( meta ).uuid;
		if ( this.specularMap && this.specularMap.isTexture ) data.specularMap = this.specularMap.toJSON( meta ).uuid;

		if ( this.envMap && this.envMap.isTexture ) {

			data.envMap = this.envMap.toJSON( meta ).uuid;
			data.reflectivity = this.reflectivity; // Scale behind envMap

		}

		if ( this.gradientMap && this.gradientMap.isTexture ) {

			data.gradientMap = this.gradientMap.toJSON( meta ).uuid;

		}

		if ( this.size !== undefined ) data.size = this.size;
		if ( this.sizeAttenuation !== undefined ) data.sizeAttenuation = this.sizeAttenuation;

		if ( this.blending !== NormalBlending ) data.blending = this.blending;
		if ( this.shading !== SmoothShading ) data.shading = this.shading;
		if ( this.side !== FrontSide ) data.side = this.side;
		if ( this.vertexColors !== NoColors ) data.vertexColors = this.vertexColors;

		if ( this.opacity < 1 ) data.opacity = this.opacity;
		if ( this.transparent === true ) data.transparent = this.transparent;

		data.depthFunc = this.depthFunc;
		data.depthTest = this.depthTest;
		data.depthWrite = this.depthWrite;

		if ( this.alphaTest > 0 ) data.alphaTest = this.alphaTest;
		if ( this.premultipliedAlpha === true ) data.premultipliedAlpha = this.premultipliedAlpha;
		if ( this.wireframe === true ) data.wireframe = this.wireframe;
		if ( this.wireframeLinewidth > 1 ) data.wireframeLinewidth = this.wireframeLinewidth;
		if ( this.wireframeLinecap !== 'round' ) data.wireframeLinecap = this.wireframeLinecap;
		if ( this.wireframeLinejoin !== 'round' ) data.wireframeLinejoin = this.wireframeLinejoin;

		data.skinning = this.skinning;
		data.morphTargets = this.morphTargets;

		// TODO: Copied from Object3D.toJSON

		function extractFromCache( cache ) {

			var values = [];

			for ( var key in cache ) {

				var data = cache[ key ];
				delete data.metadata;
				values.push( data );

			}

			return values;

		}

		if ( isRoot ) {

			var textures = extractFromCache( meta.textures );
			var images = extractFromCache( meta.images );

			if ( textures.length > 0 ) data.textures = textures;
			if ( images.length > 0 ) data.images = images;

		}

		return data;

	},

	clone: function () {

		return new this.constructor().copy( this );

	},

	copy: function ( source ) {

		this.name = source.name;

		this.fog = source.fog;
		this.lights = source.lights;

		this.blending = source.blending;
		this.side = source.side;
		this.shading = source.shading;
		this.vertexColors = source.vertexColors;

		this.opacity = source.opacity;
		this.transparent = source.transparent;

		this.blendSrc = source.blendSrc;
		this.blendDst = source.blendDst;
		this.blendEquation = source.blendEquation;
		this.blendSrcAlpha = source.blendSrcAlpha;
		this.blendDstAlpha = source.blendDstAlpha;
		this.blendEquationAlpha = source.blendEquationAlpha;

		this.depthFunc = source.depthFunc;
		this.depthTest = source.depthTest;
		this.depthWrite = source.depthWrite;

		this.colorWrite = source.colorWrite;

		this.precision = source.precision;

		this.polygonOffset = source.polygonOffset;
		this.polygonOffsetFactor = source.polygonOffsetFactor;
		this.polygonOffsetUnits = source.polygonOffsetUnits;

		this.alphaTest = source.alphaTest;

		this.premultipliedAlpha = source.premultipliedAlpha;

		this.overdraw = source.overdraw;

		this.visible = source.visible;
		this.clipShadows = source.clipShadows;
		this.clipIntersection = source.clipIntersection;

		var srcPlanes = source.clippingPlanes,
			dstPlanes = null;

		if ( srcPlanes !== null ) {

			var n = srcPlanes.length;
			dstPlanes = new Array( n );

			for ( var i = 0; i !== n; ++ i )
				dstPlanes[ i ] = srcPlanes[ i ].clone();

		}

		this.clippingPlanes = dstPlanes;

		return this;

	},

	update: function () {

		this.dispatchEvent( { type: 'update' } );

	},

	dispose: function () {

		this.dispatchEvent( { type: 'dispose' } );

	}

};

Object.assign( Material.prototype, EventDispatcher.prototype );

/**
 * @author alteredq / http://alteredqualia.com/
 *
 * parameters = {
 *  defines: { "label" : "value" },
 *  uniforms: { "parameter1": { value: 1.0 }, "parameter2": { value2: 2 } },
 *
 *  fragmentShader: <string>,
 *  vertexShader: <string>,
 *
 *  wireframe: <boolean>,
 *  wireframeLinewidth: <float>,
 *
 *  lights: <bool>,
 *
 *  skinning: <bool>,
 *  morphTargets: <bool>,
 *  morphNormals: <bool>
 * }
 */

function ShaderMaterial( parameters ) {

	Material.call( this );

	this.type = 'ShaderMaterial';

	this.defines = {};
	this.uniforms = {};

	this.vertexShader = 'void main() {\n\tgl_Position = projectionMatrix * modelViewMatrix * vec4( position, 1.0 );\n}';
	this.fragmentShader = 'void main() {\n\tgl_FragColor = vec4( 1.0, 0.0, 0.0, 1.0 );\n}';

	this.linewidth = 1;

	this.wireframe = false;
	this.wireframeLinewidth = 1;

	this.fog = false; // set to use scene fog
	this.lights = false; // set to use scene lights
	this.clipping = false; // set to use user-defined clipping planes

	this.skinning = false; // set to use skinning attribute streams
	this.morphTargets = false; // set to use morph targets
	this.morphNormals = false; // set to use morph normals

	this.extensions = {
		derivatives: false, // set to use derivatives
		fragDepth: false, // set to use fragment depth values
		drawBuffers: false, // set to use draw buffers
		shaderTextureLOD: false // set to use shader texture LOD
	};

	// When rendered geometry doesn't include these attributes but the material does,
	// use these default values in WebGL. This avoids errors when buffer data is missing.
	this.defaultAttributeValues = {
		'color': [ 1, 1, 1 ],
		'uv': [ 0, 0 ],
		'uv2': [ 0, 0 ]
	};

	this.index0AttributeName = undefined;

	if ( parameters !== undefined ) {

		if ( parameters.attributes !== undefined ) {

			console.error( 'THREE.ShaderMaterial: attributes should now be defined in THREE.BufferGeometry instead.' );

		}

		this.setValues( parameters );

	}

}

ShaderMaterial.prototype = Object.create( Material.prototype );
ShaderMaterial.prototype.constructor = ShaderMaterial;

ShaderMaterial.prototype.isShaderMaterial = true;

ShaderMaterial.prototype.copy = function ( source ) {

	Material.prototype.copy.call( this, source );

	this.fragmentShader = source.fragmentShader;
	this.vertexShader = source.vertexShader;

	this.uniforms = UniformsUtils.clone( source.uniforms );

	this.defines = source.defines;

	this.wireframe = source.wireframe;
	this.wireframeLinewidth = source.wireframeLinewidth;

	this.lights = source.lights;
	this.clipping = source.clipping;

	this.skinning = source.skinning;

	this.morphTargets = source.morphTargets;
	this.morphNormals = source.morphNormals;

	this.extensions = source.extensions;

	return this;

};

ShaderMaterial.prototype.toJSON = function ( meta ) {

	var data = Material.prototype.toJSON.call( this, meta );

	data.uniforms = this.uniforms;
	data.vertexShader = this.vertexShader;
	data.fragmentShader = this.fragmentShader;

	return data;

};

/**
 * @author mrdoob / http://mrdoob.com/
 * @author alteredq / http://alteredqualia.com/
 * @author bhouston / https://clara.io
 * @author WestLangley / http://github.com/WestLangley
 *
 * parameters = {
 *
 *  opacity: <float>,
 *
 *  map: new THREE.Texture( <Image> ),
 *
 *  alphaMap: new THREE.Texture( <Image> ),
 *
 *  displacementMap: new THREE.Texture( <Image> ),
 *  displacementScale: <float>,
 *  displacementBias: <float>,
 *
 *  wireframe: <boolean>,
 *  wireframeLinewidth: <float>
 * }
 */

function MeshDepthMaterial( parameters ) {

	Material.call( this );

	this.type = 'MeshDepthMaterial';

	this.depthPacking = BasicDepthPacking;

	this.skinning = false;
	this.morphTargets = false;

	this.map = null;

	this.alphaMap = null;

	this.displacementMap = null;
	this.displacementScale = 1;
	this.displacementBias = 0;

	this.wireframe = false;
	this.wireframeLinewidth = 1;

	this.fog = false;
	this.lights = false;

	this.setValues( parameters );

}

MeshDepthMaterial.prototype = Object.create( Material.prototype );
MeshDepthMaterial.prototype.constructor = MeshDepthMaterial;

MeshDepthMaterial.prototype.isMeshDepthMaterial = true;

MeshDepthMaterial.prototype.copy = function ( source ) {

	Material.prototype.copy.call( this, source );

	this.depthPacking = source.depthPacking;

	this.skinning = source.skinning;
	this.morphTargets = source.morphTargets;

	this.map = source.map;

	this.alphaMap = source.alphaMap;

	this.displacementMap = source.displacementMap;
	this.displacementScale = source.displacementScale;
	this.displacementBias = source.displacementBias;

	this.wireframe = source.wireframe;
	this.wireframeLinewidth = source.wireframeLinewidth;

	return this;

};

/**
 * @author bhouston / http://clara.io
 * @author WestLangley / http://github.com/WestLangley
 */

function Box3( min, max ) {

	this.min = ( min !== undefined ) ? min : new Vector3( + Infinity, + Infinity, + Infinity );
	this.max = ( max !== undefined ) ? max : new Vector3( - Infinity, - Infinity, - Infinity );

}

Box3.prototype = {

	constructor: Box3,

	isBox3: true,

	set: function ( min, max ) {

		this.min.copy( min );
		this.max.copy( max );

		return this;

	},

	setFromArray: function ( array ) {

		var minX = + Infinity;
		var minY = + Infinity;
		var minZ = + Infinity;

		var maxX = - Infinity;
		var maxY = - Infinity;
		var maxZ = - Infinity;

		for ( var i = 0, l = array.length; i < l; i += 3 ) {

			var x = array[ i ];
			var y = array[ i + 1 ];
			var z = array[ i + 2 ];

			if ( x < minX ) minX = x;
			if ( y < minY ) minY = y;
			if ( z < minZ ) minZ = z;

			if ( x > maxX ) maxX = x;
			if ( y > maxY ) maxY = y;
			if ( z > maxZ ) maxZ = z;

		}

		this.min.set( minX, minY, minZ );
		this.max.set( maxX, maxY, maxZ );

		return this;

	},

	setFromBufferAttribute: function ( attribute ) {

		var minX = + Infinity;
		var minY = + Infinity;
		var minZ = + Infinity;

		var maxX = - Infinity;
		var maxY = - Infinity;
		var maxZ = - Infinity;

		for ( var i = 0, l = attribute.count; i < l; i ++ ) {

			var x = attribute.getX( i );
			var y = attribute.getY( i );
			var z = attribute.getZ( i );

			if ( x < minX ) minX = x;
			if ( y < minY ) minY = y;
			if ( z < minZ ) minZ = z;

			if ( x > maxX ) maxX = x;
			if ( y > maxY ) maxY = y;
			if ( z > maxZ ) maxZ = z;

		}

		this.min.set( minX, minY, minZ );
		this.max.set( maxX, maxY, maxZ );

		return this;

	},

	setFromPoints: function ( points ) {

		this.makeEmpty();

		for ( var i = 0, il = points.length; i < il; i ++ ) {

			this.expandByPoint( points[ i ] );

		}

		return this;

	},

	setFromCenterAndSize: function () {

		var v1 = new Vector3();

		return function setFromCenterAndSize( center, size ) {

			var halfSize = v1.copy( size ).multiplyScalar( 0.5 );

			this.min.copy( center ).sub( halfSize );
			this.max.copy( center ).add( halfSize );

			return this;

		};

	}(),

	setFromObject: function ( object ) {

		this.makeEmpty();

		return this.expandByObject( object );

	},

	clone: function () {

		return new this.constructor().copy( this );

	},

	copy: function ( box ) {

		this.min.copy( box.min );
		this.max.copy( box.max );

		return this;

	},

	makeEmpty: function () {

		this.min.x = this.min.y = this.min.z = + Infinity;
		this.max.x = this.max.y = this.max.z = - Infinity;

		return this;

	},

	isEmpty: function () {

		// this is a more robust check for empty than ( volume <= 0 ) because volume can get positive with two negative axes

		return ( this.max.x < this.min.x ) || ( this.max.y < this.min.y ) || ( this.max.z < this.min.z );

	},

	getCenter: function ( optionalTarget ) {

		var result = optionalTarget || new Vector3();
		return this.isEmpty() ? result.set( 0, 0, 0 ) : result.addVectors( this.min, this.max ).multiplyScalar( 0.5 );

	},

	getSize: function ( optionalTarget ) {

		var result = optionalTarget || new Vector3();
		return this.isEmpty() ? result.set( 0, 0, 0 ) : result.subVectors( this.max, this.min );

	},

	expandByPoint: function ( point ) {

		this.min.min( point );
		this.max.max( point );

		return this;

	},

	expandByVector: function ( vector ) {

		this.min.sub( vector );
		this.max.add( vector );

		return this;

	},

	expandByScalar: function ( scalar ) {

		this.min.addScalar( - scalar );
		this.max.addScalar( scalar );

		return this;

	},

	expandByObject: function () {

		// Computes the world-axis-aligned bounding box of an object (including its children),
		// accounting for both the object's, and children's, world transforms

		var v1 = new Vector3();

		return function expandByObject( object ) {

			var scope = this;

			object.updateMatrixWorld( true );

			object.traverse( function ( node ) {

				var i, l;

				var geometry = node.geometry;

				if ( geometry !== undefined ) {

					if ( geometry.isGeometry ) {

						var vertices = geometry.vertices;

						for ( i = 0, l = vertices.length; i < l; i ++ ) {

							v1.copy( vertices[ i ] );
							v1.applyMatrix4( node.matrixWorld );

							scope.expandByPoint( v1 );

						}

					} else if ( geometry.isBufferGeometry ) {

						var attribute = geometry.attributes.position;

						if ( attribute !== undefined ) {

							for ( i = 0, l = attribute.count; i < l; i ++ ) {

								v1.fromBufferAttribute( attribute, i ).applyMatrix4( node.matrixWorld );

								scope.expandByPoint( v1 );

							}

						}

					}

				}

			} );

			return this;

		};

	}(),

	containsPoint: function ( point ) {

		return point.x < this.min.x || point.x > this.max.x ||
			point.y < this.min.y || point.y > this.max.y ||
			point.z < this.min.z || point.z > this.max.z ? false : true;

	},

	containsBox: function ( box ) {

		return this.min.x <= box.min.x && box.max.x <= this.max.x &&
			this.min.y <= box.min.y && box.max.y <= this.max.y &&
			this.min.z <= box.min.z && box.max.z <= this.max.z;

	},

	getParameter: function ( point, optionalTarget ) {

		// This can potentially have a divide by zero if the box
		// has a size dimension of 0.

		var result = optionalTarget || new Vector3();

		return result.set(
			( point.x - this.min.x ) / ( this.max.x - this.min.x ),
			( point.y - this.min.y ) / ( this.max.y - this.min.y ),
			( point.z - this.min.z ) / ( this.max.z - this.min.z )
		);

	},

	intersectsBox: function ( box ) {

		// using 6 splitting planes to rule out intersections.
		return box.max.x < this.min.x || box.min.x > this.max.x ||
			box.max.y < this.min.y || box.min.y > this.max.y ||
			box.max.z < this.min.z || box.min.z > this.max.z ? false : true;

	},

	intersectsSphere: ( function () {

		var closestPoint;

		return function intersectsSphere( sphere ) {

			if ( closestPoint === undefined ) closestPoint = new Vector3();

			// Find the point on the AABB closest to the sphere center.
			this.clampPoint( sphere.center, closestPoint );

			// If that point is inside the sphere, the AABB and sphere intersect.
			return closestPoint.distanceToSquared( sphere.center ) <= ( sphere.radius * sphere.radius );

		};

	} )(),

	intersectsPlane: function ( plane ) {

		// We compute the minimum and maximum dot product values. If those values
		// are on the same side (back or front) of the plane, then there is no intersection.

		var min, max;

		if ( plane.normal.x > 0 ) {

			min = plane.normal.x * this.min.x;
			max = plane.normal.x * this.max.x;

		} else {

			min = plane.normal.x * this.max.x;
			max = plane.normal.x * this.min.x;

		}

		if ( plane.normal.y > 0 ) {

			min += plane.normal.y * this.min.y;
			max += plane.normal.y * this.max.y;

		} else {

			min += plane.normal.y * this.max.y;
			max += plane.normal.y * this.min.y;

		}

		if ( plane.normal.z > 0 ) {

			min += plane.normal.z * this.min.z;
			max += plane.normal.z * this.max.z;

		} else {

			min += plane.normal.z * this.max.z;
			max += plane.normal.z * this.min.z;

		}

		return ( min <= plane.constant && max >= plane.constant );

	},

	clampPoint: function ( point, optionalTarget ) {

		var result = optionalTarget || new Vector3();
		return result.copy( point ).clamp( this.min, this.max );

	},

	distanceToPoint: function () {

		var v1 = new Vector3();

		return function distanceToPoint( point ) {

			var clampedPoint = v1.copy( point ).clamp( this.min, this.max );
			return clampedPoint.sub( point ).length();

		};

	}(),

	getBoundingSphere: function () {

		var v1 = new Vector3();

		return function getBoundingSphere( optionalTarget ) {

			var result = optionalTarget || new Sphere();

			this.getCenter( result.center );

			result.radius = this.getSize( v1 ).length() * 0.5;

			return result;

		};

	}(),

	intersect: function ( box ) {

		this.min.max( box.min );
		this.max.min( box.max );

		// ensure that if there is no overlap, the result is fully empty, not slightly empty with non-inf/+inf values that will cause subsequence intersects to erroneously return valid values.
		if( this.isEmpty() ) this.makeEmpty();

		return this;

	},

	union: function ( box ) {

		this.min.min( box.min );
		this.max.max( box.max );

		return this;

	},

	applyMatrix4: function () {

		var points = [
			new Vector3(),
			new Vector3(),
			new Vector3(),
			new Vector3(),
			new Vector3(),
			new Vector3(),
			new Vector3(),
			new Vector3()
		];

		return function applyMatrix4( matrix ) {

			// transform of empty box is an empty box.
			if( this.isEmpty() ) return this;

			// NOTE: I am using a binary pattern to specify all 2^3 combinations below
			points[ 0 ].set( this.min.x, this.min.y, this.min.z ).applyMatrix4( matrix ); // 000
			points[ 1 ].set( this.min.x, this.min.y, this.max.z ).applyMatrix4( matrix ); // 001
			points[ 2 ].set( this.min.x, this.max.y, this.min.z ).applyMatrix4( matrix ); // 010
			points[ 3 ].set( this.min.x, this.max.y, this.max.z ).applyMatrix4( matrix ); // 011
			points[ 4 ].set( this.max.x, this.min.y, this.min.z ).applyMatrix4( matrix ); // 100
			points[ 5 ].set( this.max.x, this.min.y, this.max.z ).applyMatrix4( matrix ); // 101
			points[ 6 ].set( this.max.x, this.max.y, this.min.z ).applyMatrix4( matrix ); // 110
			points[ 7 ].set( this.max.x, this.max.y, this.max.z ).applyMatrix4( matrix );	// 111

			this.setFromPoints( points );

			return this;

		};

	}(),

	translate: function ( offset ) {

		this.min.add( offset );
		this.max.add( offset );

		return this;

	},

	equals: function ( box ) {

		return box.min.equals( this.min ) && box.max.equals( this.max );

	}

};

/**
 * @author bhouston / http://clara.io
 * @author mrdoob / http://mrdoob.com/
 */

function Sphere( center, radius ) {

	this.center = ( center !== undefined ) ? center : new Vector3();
	this.radius = ( radius !== undefined ) ? radius : 0;

}

Sphere.prototype = {

	constructor: Sphere,

	set: function ( center, radius ) {

		this.center.copy( center );
		this.radius = radius;

		return this;

	},

	setFromPoints: function () {

		var box;

		return function setFromPoints( points, optionalCenter ) {

			if ( box === undefined ) box = new Box3(); // see #10547

			var center = this.center;

			if ( optionalCenter !== undefined ) {

				center.copy( optionalCenter );

			} else {

				box.setFromPoints( points ).getCenter( center );

			}

			var maxRadiusSq = 0;

			for ( var i = 0, il = points.length; i < il; i ++ ) {

				maxRadiusSq = Math.max( maxRadiusSq, center.distanceToSquared( points[ i ] ) );

			}

			this.radius = Math.sqrt( maxRadiusSq );

			return this;

		};

	}(),

	clone: function () {

		return new this.constructor().copy( this );

	},

	copy: function ( sphere ) {

		this.center.copy( sphere.center );
		this.radius = sphere.radius;

		return this;

	},

	empty: function () {

		return ( this.radius <= 0 );

	},

	containsPoint: function ( point ) {

		return ( point.distanceToSquared( this.center ) <= ( this.radius * this.radius ) );

	},

	distanceToPoint: function ( point ) {

		return ( point.distanceTo( this.center ) - this.radius );

	},

	intersectsSphere: function ( sphere ) {

		var radiusSum = this.radius + sphere.radius;

		return sphere.center.distanceToSquared( this.center ) <= ( radiusSum * radiusSum );

	},

	intersectsBox: function ( box ) {

		return box.intersectsSphere( this );

	},

	intersectsPlane: function ( plane ) {

		// We use the following equation to compute the signed distance from
		// the center of the sphere to the plane.
		//
		// distance = q * n - d
		//
		// If this distance is greater than the radius of the sphere,
		// then there is no intersection.

		return Math.abs( this.center.dot( plane.normal ) - plane.constant ) <= this.radius;

	},

	clampPoint: function ( point, optionalTarget ) {

		var deltaLengthSq = this.center.distanceToSquared( point );

		var result = optionalTarget || new Vector3();

		result.copy( point );

		if ( deltaLengthSq > ( this.radius * this.radius ) ) {

			result.sub( this.center ).normalize();
			result.multiplyScalar( this.radius ).add( this.center );

		}

		return result;

	},

	getBoundingBox: function ( optionalTarget ) {

		var box = optionalTarget || new Box3();

		box.set( this.center, this.center );
		box.expandByScalar( this.radius );

		return box;

	},

	applyMatrix4: function ( matrix ) {

		this.center.applyMatrix4( matrix );
		this.radius = this.radius * matrix.getMaxScaleOnAxis();

		return this;

	},

	translate: function ( offset ) {

		this.center.add( offset );

		return this;

	},

	equals: function ( sphere ) {

		return sphere.center.equals( this.center ) && ( sphere.radius === this.radius );

	}

};

/**
 * @author alteredq / http://alteredqualia.com/
 * @author WestLangley / http://github.com/WestLangley
 * @author bhouston / http://clara.io
 * @author tschw
 */

function Matrix3() {

	this.elements = new Float32Array( [

		1, 0, 0,
		0, 1, 0,
		0, 0, 1

	] );

	if ( arguments.length > 0 ) {

		console.error( 'THREE.Matrix3: the constructor no longer reads arguments. use .set() instead.' );

	}

}

Matrix3.prototype = {

	constructor: Matrix3,

	isMatrix3: true,

	set: function ( n11, n12, n13, n21, n22, n23, n31, n32, n33 ) {

		var te = this.elements;

		te[ 0 ] = n11; te[ 1 ] = n21; te[ 2 ] = n31;
		te[ 3 ] = n12; te[ 4 ] = n22; te[ 5 ] = n32;
		te[ 6 ] = n13; te[ 7 ] = n23; te[ 8 ] = n33;

		return this;

	},

	identity: function () {

		this.set(

			1, 0, 0,
			0, 1, 0,
			0, 0, 1

		);

		return this;

	},

	clone: function () {

		return new this.constructor().fromArray( this.elements );

	},

	copy: function ( m ) {

		var me = m.elements;

		this.set(

			me[ 0 ], me[ 3 ], me[ 6 ],
			me[ 1 ], me[ 4 ], me[ 7 ],
			me[ 2 ], me[ 5 ], me[ 8 ]

		);

		return this;

	},

	setFromMatrix4: function( m ) {

		var me = m.elements;

		this.set(

			me[ 0 ], me[ 4 ], me[  8 ],
			me[ 1 ], me[ 5 ], me[  9 ],
			me[ 2 ], me[ 6 ], me[ 10 ]

		);

		return this;

	},

	applyToBufferAttribute: function () {

		var v1;

		return function applyToBufferAttribute( attribute ) {

			if ( v1 === undefined ) v1 = new Vector3();

			for ( var i = 0, l = attribute.count; i < l; i ++ ) {

				v1.x = attribute.getX( i );
				v1.y = attribute.getY( i );
				v1.z = attribute.getZ( i );

				v1.applyMatrix3( this );

				attribute.setXYZ( i, v1.x, v1.y, v1.z );

			}

			return attribute;

		};

	}(),

	multiplyScalar: function ( s ) {

		var te = this.elements;

		te[ 0 ] *= s; te[ 3 ] *= s; te[ 6 ] *= s;
		te[ 1 ] *= s; te[ 4 ] *= s; te[ 7 ] *= s;
		te[ 2 ] *= s; te[ 5 ] *= s; te[ 8 ] *= s;

		return this;

	},

	determinant: function () {

		var te = this.elements;

		var a = te[ 0 ], b = te[ 1 ], c = te[ 2 ],
			d = te[ 3 ], e = te[ 4 ], f = te[ 5 ],
			g = te[ 6 ], h = te[ 7 ], i = te[ 8 ];

		return a * e * i - a * f * h - b * d * i + b * f * g + c * d * h - c * e * g;

	},

	getInverse: function ( matrix, throwOnDegenerate ) {

		if ( matrix && matrix.isMatrix4 ) {

			console.error( "THREE.Matrix3.getInverse no longer takes a Matrix4 argument." );

		}

		var me = matrix.elements,
			te = this.elements,

			n11 = me[ 0 ], n21 = me[ 1 ], n31 = me[ 2 ],
			n12 = me[ 3 ], n22 = me[ 4 ], n32 = me[ 5 ],
			n13 = me[ 6 ], n23 = me[ 7 ], n33 = me[ 8 ],

			t11 = n33 * n22 - n32 * n23,
			t12 = n32 * n13 - n33 * n12,
			t13 = n23 * n12 - n22 * n13,

			det = n11 * t11 + n21 * t12 + n31 * t13;

		if ( det === 0 ) {

			var msg = "THREE.Matrix3.getInverse(): can't invert matrix, determinant is 0";

			if ( throwOnDegenerate === true ) {

				throw new Error( msg );

			} else {

				console.warn( msg );

			}

			return this.identity();
		}

		var detInv = 1 / det;

		te[ 0 ] = t11 * detInv;
		te[ 1 ] = ( n31 * n23 - n33 * n21 ) * detInv;
		te[ 2 ] = ( n32 * n21 - n31 * n22 ) * detInv;

		te[ 3 ] = t12 * detInv;
		te[ 4 ] = ( n33 * n11 - n31 * n13 ) * detInv;
		te[ 5 ] = ( n31 * n12 - n32 * n11 ) * detInv;

		te[ 6 ] = t13 * detInv;
		te[ 7 ] = ( n21 * n13 - n23 * n11 ) * detInv;
		te[ 8 ] = ( n22 * n11 - n21 * n12 ) * detInv;

		return this;

	},

	transpose: function () {

		var tmp, m = this.elements;

		tmp = m[ 1 ]; m[ 1 ] = m[ 3 ]; m[ 3 ] = tmp;
		tmp = m[ 2 ]; m[ 2 ] = m[ 6 ]; m[ 6 ] = tmp;
		tmp = m[ 5 ]; m[ 5 ] = m[ 7 ]; m[ 7 ] = tmp;

		return this;

	},

	getNormalMatrix: function ( matrix4 ) {

		return this.setFromMatrix4( matrix4 ).getInverse( this ).transpose();

	},

	transposeIntoArray: function ( r ) {

		var m = this.elements;

		r[ 0 ] = m[ 0 ];
		r[ 1 ] = m[ 3 ];
		r[ 2 ] = m[ 6 ];
		r[ 3 ] = m[ 1 ];
		r[ 4 ] = m[ 4 ];
		r[ 5 ] = m[ 7 ];
		r[ 6 ] = m[ 2 ];
		r[ 7 ] = m[ 5 ];
		r[ 8 ] = m[ 8 ];

		return this;

	},

	fromArray: function ( array, offset ) {

		if ( offset === undefined ) offset = 0;

		for( var i = 0; i < 9; i ++ ) {

			this.elements[ i ] = array[ i + offset ];

		}

		return this;

	},

	toArray: function ( array, offset ) {

		if ( array === undefined ) array = [];
		if ( offset === undefined ) offset = 0;

		var te = this.elements;

		array[ offset ] = te[ 0 ];
		array[ offset + 1 ] = te[ 1 ];
		array[ offset + 2 ] = te[ 2 ];

		array[ offset + 3 ] = te[ 3 ];
		array[ offset + 4 ] = te[ 4 ];
		array[ offset + 5 ] = te[ 5 ];

		array[ offset + 6 ] = te[ 6 ];
		array[ offset + 7 ] = te[ 7 ];
		array[ offset + 8 ]  = te[ 8 ];

		return array;

	}

};

/**
 * @author bhouston / http://clara.io
 */

function Plane( normal, constant ) {

	this.normal = ( normal !== undefined ) ? normal : new Vector3( 1, 0, 0 );
	this.constant = ( constant !== undefined ) ? constant : 0;

}

Plane.prototype = {

	constructor: Plane,

	set: function ( normal, constant ) {

		this.normal.copy( normal );
		this.constant = constant;

		return this;

	},

	setComponents: function ( x, y, z, w ) {

		this.normal.set( x, y, z );
		this.constant = w;

		return this;

	},

	setFromNormalAndCoplanarPoint: function ( normal, point ) {

		this.normal.copy( normal );
		this.constant = - point.dot( this.normal );	// must be this.normal, not normal, as this.normal is normalized

		return this;

	},

	setFromCoplanarPoints: function () {

		var v1 = new Vector3();
		var v2 = new Vector3();

		return function setFromCoplanarPoints( a, b, c ) {

			var normal = v1.subVectors( c, b ).cross( v2.subVectors( a, b ) ).normalize();

			// Q: should an error be thrown if normal is zero (e.g. degenerate plane)?

			this.setFromNormalAndCoplanarPoint( normal, a );

			return this;

		};

	}(),

	clone: function () {

		return new this.constructor().copy( this );

	},

	copy: function ( plane ) {

		this.normal.copy( plane.normal );
		this.constant = plane.constant;

		return this;

	},

	normalize: function () {

		// Note: will lead to a divide by zero if the plane is invalid.

		var inverseNormalLength = 1.0 / this.normal.length();
		this.normal.multiplyScalar( inverseNormalLength );
		this.constant *= inverseNormalLength;

		return this;

	},

	negate: function () {

		this.constant *= - 1;
		this.normal.negate();

		return this;

	},

	distanceToPoint: function ( point ) {

		return this.normal.dot( point ) + this.constant;

	},

	distanceToSphere: function ( sphere ) {

		return this.distanceToPoint( sphere.center ) - sphere.radius;

	},

	projectPoint: function ( point, optionalTarget ) {

		return this.orthoPoint( point, optionalTarget ).sub( point ).negate();

	},

	orthoPoint: function ( point, optionalTarget ) {

		var perpendicularMagnitude = this.distanceToPoint( point );

		var result = optionalTarget || new Vector3();
		return result.copy( this.normal ).multiplyScalar( perpendicularMagnitude );

	},

	intersectLine: function () {

		var v1 = new Vector3();

		return function intersectLine( line, optionalTarget ) {

			var result = optionalTarget || new Vector3();

			var direction = line.delta( v1 );

			var denominator = this.normal.dot( direction );

			if ( denominator === 0 ) {

				// line is coplanar, return origin
				if ( this.distanceToPoint( line.start ) === 0 ) {

					return result.copy( line.start );

				}

				// Unsure if this is the correct method to handle this case.
				return undefined;

			}

			var t = - ( line.start.dot( this.normal ) + this.constant ) / denominator;

			if ( t < 0 || t > 1 ) {

				return undefined;

			}

			return result.copy( direction ).multiplyScalar( t ).add( line.start );

		};

	}(),

	intersectsLine: function ( line ) {

		// Note: this tests if a line intersects the plane, not whether it (or its end-points) are coplanar with it.

		var startSign = this.distanceToPoint( line.start );
		var endSign = this.distanceToPoint( line.end );

		return ( startSign < 0 && endSign > 0 ) || ( endSign < 0 && startSign > 0 );

	},

	intersectsBox: function ( box ) {

		return box.intersectsPlane( this );

	},

	intersectsSphere: function ( sphere ) {

		return sphere.intersectsPlane( this );

	},

	coplanarPoint: function ( optionalTarget ) {

		var result = optionalTarget || new Vector3();
		return result.copy( this.normal ).multiplyScalar( - this.constant );

	},

	applyMatrix4: function () {

		var v1 = new Vector3();
		var m1 = new Matrix3();

		return function applyMatrix4( matrix, optionalNormalMatrix ) {

			var referencePoint = this.coplanarPoint( v1 ).applyMatrix4( matrix );

			// transform normal based on theory here:
			// http://www.songho.ca/opengl/gl_normaltransform.html
			var normalMatrix = optionalNormalMatrix || m1.getNormalMatrix( matrix );
			var normal = this.normal.applyMatrix3( normalMatrix ).normalize();

			// recalculate constant (like in setFromNormalAndCoplanarPoint)
			this.constant = - referencePoint.dot( normal );

			return this;

		};

	}(),

	translate: function ( offset ) {

		this.constant = this.constant - offset.dot( this.normal );

		return this;

	},

	equals: function ( plane ) {

		return plane.normal.equals( this.normal ) && ( plane.constant === this.constant );

	}

};

/**
 * @author mrdoob / http://mrdoob.com/
 * @author alteredq / http://alteredqualia.com/
 * @author bhouston / http://clara.io
 */

function Frustum( p0, p1, p2, p3, p4, p5 ) {

	this.planes = [

		( p0 !== undefined ) ? p0 : new Plane(),
		( p1 !== undefined ) ? p1 : new Plane(),
		( p2 !== undefined ) ? p2 : new Plane(),
		( p3 !== undefined ) ? p3 : new Plane(),
		( p4 !== undefined ) ? p4 : new Plane(),
		( p5 !== undefined ) ? p5 : new Plane()

	];

}

Frustum.prototype = {

	constructor: Frustum,

	set: function ( p0, p1, p2, p3, p4, p5 ) {

		var planes = this.planes;

		planes[ 0 ].copy( p0 );
		planes[ 1 ].copy( p1 );
		planes[ 2 ].copy( p2 );
		planes[ 3 ].copy( p3 );
		planes[ 4 ].copy( p4 );
		planes[ 5 ].copy( p5 );

		return this;

	},

	clone: function () {

		return new this.constructor().copy( this );

	},

	copy: function ( frustum ) {

		var planes = this.planes;

		for ( var i = 0; i < 6; i ++ ) {

			planes[ i ].copy( frustum.planes[ i ] );

		}

		return this;

	},

	setFromMatrix: function ( m ) {

		var planes = this.planes;
		var me = m.elements;
		var me0 = me[ 0 ], me1 = me[ 1 ], me2 = me[ 2 ], me3 = me[ 3 ];
		var me4 = me[ 4 ], me5 = me[ 5 ], me6 = me[ 6 ], me7 = me[ 7 ];
		var me8 = me[ 8 ], me9 = me[ 9 ], me10 = me[ 10 ], me11 = me[ 11 ];
		var me12 = me[ 12 ], me13 = me[ 13 ], me14 = me[ 14 ], me15 = me[ 15 ];

		planes[ 0 ].setComponents( me3 - me0, me7 - me4, me11 - me8, me15 - me12 ).normalize();
		planes[ 1 ].setComponents( me3 + me0, me7 + me4, me11 + me8, me15 + me12 ).normalize();
		planes[ 2 ].setComponents( me3 + me1, me7 + me5, me11 + me9, me15 + me13 ).normalize();
		planes[ 3 ].setComponents( me3 - me1, me7 - me5, me11 - me9, me15 - me13 ).normalize();
		planes[ 4 ].setComponents( me3 - me2, me7 - me6, me11 - me10, me15 - me14 ).normalize();
		planes[ 5 ].setComponents( me3 + me2, me7 + me6, me11 + me10, me15 + me14 ).normalize();

		return this;

	},

	intersectsObject: function () {

		var sphere = new Sphere();

		return function intersectsObject( object ) {

			var geometry = object.geometry;

			if ( geometry.boundingSphere === null )
				geometry.computeBoundingSphere();

			sphere.copy( geometry.boundingSphere )
				.applyMatrix4( object.matrixWorld );

			return this.intersectsSphere( sphere );

		};

	}(),

	intersectsSprite: function () {

		var sphere = new Sphere();

		return function intersectsSprite( sprite ) {

			sphere.center.set( 0, 0, 0 );
			sphere.radius = 0.7071067811865476;
			sphere.applyMatrix4( sprite.matrixWorld );

			return this.intersectsSphere( sphere );

		};

	}(),

	intersectsSphere: function ( sphere ) {

		var planes = this.planes;
		var center = sphere.center;
		var negRadius = - sphere.radius;

		for ( var i = 0; i < 6; i ++ ) {

			var distance = planes[ i ].distanceToPoint( center );

			if ( distance < negRadius ) {

				return false;

			}

		}

		return true;

	},

	intersectsBox: function () {

		var p1 = new Vector3(),
			p2 = new Vector3();

		return function intersectsBox( box ) {

			var planes = this.planes;

			for ( var i = 0; i < 6 ; i ++ ) {

				var plane = planes[ i ];

				p1.x = plane.normal.x > 0 ? box.min.x : box.max.x;
				p2.x = plane.normal.x > 0 ? box.max.x : box.min.x;
				p1.y = plane.normal.y > 0 ? box.min.y : box.max.y;
				p2.y = plane.normal.y > 0 ? box.max.y : box.min.y;
				p1.z = plane.normal.z > 0 ? box.min.z : box.max.z;
				p2.z = plane.normal.z > 0 ? box.max.z : box.min.z;

				var d1 = plane.distanceToPoint( p1 );
				var d2 = plane.distanceToPoint( p2 );

				// if both outside plane, no intersection

				if ( d1 < 0 && d2 < 0 ) {

					return false;

				}

			}

			return true;

		};

	}(),


	containsPoint: function ( point ) {

		var planes = this.planes;

		for ( var i = 0; i < 6; i ++ ) {

			if ( planes[ i ].distanceToPoint( point ) < 0 ) {

				return false;

			}

		}

		return true;

	}

};

/**
 * @author alteredq / http://alteredqualia.com/
 * @author mrdoob / http://mrdoob.com/
 */

function WebGLShadowMap( _renderer, _lights, _objects, capabilities ) {

	var _gl = _renderer.context,
	_state = _renderer.state,
	_frustum = new Frustum(),
	_projScreenMatrix = new Matrix4(),

	_lightShadows = _lights.shadows,

	_shadowMapSize = new Vector2(),
	_maxShadowMapSize = new Vector2( capabilities.maxTextureSize, capabilities.maxTextureSize ),

	_lookTarget = new Vector3(),
	_lightPositionWorld = new Vector3(),

	_renderList = [],

	_MorphingFlag = 1,
	_SkinningFlag = 2,

	_NumberOfMaterialVariants = ( _MorphingFlag | _SkinningFlag ) + 1,

	_depthMaterials = new Array( _NumberOfMaterialVariants ),
	_distanceMaterials = new Array( _NumberOfMaterialVariants ),

	_materialCache = {};

	var cubeDirections = [
		new Vector3( 1, 0, 0 ), new Vector3( - 1, 0, 0 ), new Vector3( 0, 0, 1 ),
		new Vector3( 0, 0, - 1 ), new Vector3( 0, 1, 0 ), new Vector3( 0, - 1, 0 )
	];

	var cubeUps = [
		new Vector3( 0, 1, 0 ), new Vector3( 0, 1, 0 ), new Vector3( 0, 1, 0 ),
		new Vector3( 0, 1, 0 ), new Vector3( 0, 0, 1 ),	new Vector3( 0, 0, - 1 )
	];

	var cube2DViewPorts = [
		new Vector4(), new Vector4(), new Vector4(),
		new Vector4(), new Vector4(), new Vector4()
	];

	// init

	var depthMaterialTemplate = new MeshDepthMaterial();
	depthMaterialTemplate.depthPacking = RGBADepthPacking;
	depthMaterialTemplate.clipping = true;

	var distanceShader = ShaderLib[ "distanceRGBA" ];
	var distanceUniforms = UniformsUtils.clone( distanceShader.uniforms );

	for ( var i = 0; i !== _NumberOfMaterialVariants; ++ i ) {

		var useMorphing = ( i & _MorphingFlag ) !== 0;
		var useSkinning = ( i & _SkinningFlag ) !== 0;

		var depthMaterial = depthMaterialTemplate.clone();
		depthMaterial.morphTargets = useMorphing;
		depthMaterial.skinning = useSkinning;

		_depthMaterials[ i ] = depthMaterial;

		var distanceMaterial = new ShaderMaterial( {
			defines: {
				'USE_SHADOWMAP': ''
			},
			uniforms: distanceUniforms,
			vertexShader: distanceShader.vertexShader,
			fragmentShader: distanceShader.fragmentShader,
			morphTargets: useMorphing,
			skinning: useSkinning,
			clipping: true
		} );

		_distanceMaterials[ i ] = distanceMaterial;

	}

	//

	var scope = this;

	this.enabled = false;

	this.autoUpdate = true;
	this.needsUpdate = false;

	this.type = PCFShadowMap;

	this.renderReverseSided = true;
	this.renderSingleSided = true;

	this.render = function ( scene, camera ) {

		if ( scope.enabled === false ) return;
		if ( scope.autoUpdate === false && scope.needsUpdate === false ) return;

		if ( _lightShadows.length === 0 ) return;

		// Set GL state for depth map.
		_state.buffers.color.setClear( 1, 1, 1, 1 );
		_state.disable( _gl.BLEND );
		_state.setDepthTest( true );
		_state.setScissorTest( false );

		// render depth map

		var faceCount, isPointLight;

		for ( var i = 0, il = _lightShadows.length; i < il; i ++ ) {

			var light = _lightShadows[ i ];
			var shadow = light.shadow;

			if ( shadow === undefined ) {

				console.warn( 'THREE.WebGLShadowMap:', light, 'has no shadow.' );
				continue;

			}

			var shadowCamera = shadow.camera;

			_shadowMapSize.copy( shadow.mapSize );
			_shadowMapSize.min( _maxShadowMapSize );

			if ( light && light.isPointLight ) {

				faceCount = 6;
				isPointLight = true;

				var vpWidth = _shadowMapSize.x;
				var vpHeight = _shadowMapSize.y;

				// These viewports map a cube-map onto a 2D texture with the
				// following orientation:
				//
				//  xzXZ
				//   y Y
				//
				// X - Positive x direction
				// x - Negative x direction
				// Y - Positive y direction
				// y - Negative y direction
				// Z - Positive z direction
				// z - Negative z direction

				// positive X
				cube2DViewPorts[ 0 ].set( vpWidth * 2, vpHeight, vpWidth, vpHeight );
				// negative X
				cube2DViewPorts[ 1 ].set( 0, vpHeight, vpWidth, vpHeight );
				// positive Z
				cube2DViewPorts[ 2 ].set( vpWidth * 3, vpHeight, vpWidth, vpHeight );
				// negative Z
				cube2DViewPorts[ 3 ].set( vpWidth, vpHeight, vpWidth, vpHeight );
				// positive Y
				cube2DViewPorts[ 4 ].set( vpWidth * 3, 0, vpWidth, vpHeight );
				// negative Y
				cube2DViewPorts[ 5 ].set( vpWidth, 0, vpWidth, vpHeight );

				_shadowMapSize.x *= 4.0;
				_shadowMapSize.y *= 2.0;

			} else {

				faceCount = 1;
				isPointLight = false;

			}

			if ( shadow.map === null ) {

				var pars = { minFilter: NearestFilter, magFilter: NearestFilter, format: RGBAFormat };

				shadow.map = new WebGLRenderTarget( _shadowMapSize.x, _shadowMapSize.y, pars );

				shadowCamera.updateProjectionMatrix();

			}

			if ( shadow.isSpotLightShadow ) {

				shadow.update( light );

			}

			// TODO (abelnation / sam-g-steel): is this needed?
			if (shadow && shadow.isRectAreaLightShadow ) {

				shadow.update( light );

			}

			var shadowMap = shadow.map;
			var shadowMatrix = shadow.matrix;

			_lightPositionWorld.setFromMatrixPosition( light.matrixWorld );
			shadowCamera.position.copy( _lightPositionWorld );

			_renderer.setRenderTarget( shadowMap );
			_renderer.clear();

			// render shadow map for each cube face (if omni-directional) or
			// run a single pass if not

			for ( var face = 0; face < faceCount; face ++ ) {

				if ( isPointLight ) {

					_lookTarget.copy( shadowCamera.position );
					_lookTarget.add( cubeDirections[ face ] );
					shadowCamera.up.copy( cubeUps[ face ] );
					shadowCamera.lookAt( _lookTarget );

					var vpDimensions = cube2DViewPorts[ face ];
					_state.viewport( vpDimensions );

				} else {

					_lookTarget.setFromMatrixPosition( light.target.matrixWorld );
					shadowCamera.lookAt( _lookTarget );

				}

				shadowCamera.updateMatrixWorld();
				shadowCamera.matrixWorldInverse.getInverse( shadowCamera.matrixWorld );

				// compute shadow matrix

				shadowMatrix.set(
					0.5, 0.0, 0.0, 0.5,
					0.0, 0.5, 0.0, 0.5,
					0.0, 0.0, 0.5, 0.5,
					0.0, 0.0, 0.0, 1.0
				);

				shadowMatrix.multiply( shadowCamera.projectionMatrix );
				shadowMatrix.multiply( shadowCamera.matrixWorldInverse );

				// update camera matrices and frustum

				_projScreenMatrix.multiplyMatrices( shadowCamera.projectionMatrix, shadowCamera.matrixWorldInverse );
				_frustum.setFromMatrix( _projScreenMatrix );

				// set object matrices & frustum culling

				_renderList.length = 0;

				projectObject( scene, camera, shadowCamera );

				// render shadow map
				// render regular objects

				for ( var j = 0, jl = _renderList.length; j < jl; j ++ ) {

					var object = _renderList[ j ];
					var geometry = _objects.update( object );
					var material = object.material;

					if ( material && material.isMultiMaterial ) {

						var groups = geometry.groups;
						var materials = material.materials;

						for ( var k = 0, kl = groups.length; k < kl; k ++ ) {

							var group = groups[ k ];
							var groupMaterial = materials[ group.materialIndex ];

							if ( groupMaterial.visible === true ) {

								var depthMaterial = getDepthMaterial( object, groupMaterial, isPointLight, _lightPositionWorld );
								_renderer.renderBufferDirect( shadowCamera, null, geometry, depthMaterial, object, group );

							}

						}

					} else {

						var depthMaterial = getDepthMaterial( object, material, isPointLight, _lightPositionWorld );
						_renderer.renderBufferDirect( shadowCamera, null, geometry, depthMaterial, object, null );

					}

				}

			}

		}

		// Restore GL state.
		var clearColor = _renderer.getClearColor(),
		clearAlpha = _renderer.getClearAlpha();
		_renderer.setClearColor( clearColor, clearAlpha );

		scope.needsUpdate = false;

	};

	function getDepthMaterial( object, material, isPointLight, lightPositionWorld ) {

		var geometry = object.geometry;

		var result = null;

		var materialVariants = _depthMaterials;
		var customMaterial = object.customDepthMaterial;

		if ( isPointLight ) {

			materialVariants = _distanceMaterials;
			customMaterial = object.customDistanceMaterial;

		}

		if ( ! customMaterial ) {

			var useMorphing = false;

			if ( material.morphTargets ) {

				if ( geometry && geometry.isBufferGeometry ) {

					useMorphing = geometry.morphAttributes && geometry.morphAttributes.position && geometry.morphAttributes.position.length > 0;

				} else if ( geometry && geometry.isGeometry ) {

					useMorphing = geometry.morphTargets && geometry.morphTargets.length > 0;

				}

			}

			var useSkinning = object.isSkinnedMesh && material.skinning;

			var variantIndex = 0;

			if ( useMorphing ) variantIndex |= _MorphingFlag;
			if ( useSkinning ) variantIndex |= _SkinningFlag;

			result = materialVariants[ variantIndex ];

		} else {

			result = customMaterial;

		}

		if ( _renderer.localClippingEnabled &&
			 material.clipShadows === true &&
				material.clippingPlanes.length !== 0 ) {

			// in this case we need a unique material instance reflecting the
			// appropriate state

			var keyA = result.uuid, keyB = material.uuid;

			var materialsForVariant = _materialCache[ keyA ];

			if ( materialsForVariant === undefined ) {

				materialsForVariant = {};
				_materialCache[ keyA ] = materialsForVariant;

			}

			var cachedMaterial = materialsForVariant[ keyB ];

			if ( cachedMaterial === undefined ) {

				cachedMaterial = result.clone();
				materialsForVariant[ keyB ] = cachedMaterial;

			}

			result = cachedMaterial;

		}

		result.visible = material.visible;
		result.wireframe = material.wireframe;

		var side = material.side;

		if ( scope.renderSingleSided && side == DoubleSide ) {

			side = FrontSide;

		}

		if ( scope.renderReverseSided ) {

			if ( side === FrontSide ) side = BackSide;
			else if ( side === BackSide ) side = FrontSide;

		}

		result.side = side;

		result.clipShadows = material.clipShadows;
		result.clippingPlanes = material.clippingPlanes;

		result.wireframeLinewidth = material.wireframeLinewidth;
		result.linewidth = material.linewidth;

		if ( isPointLight && result.uniforms.lightPos !== undefined ) {

			result.uniforms.lightPos.value.copy( lightPositionWorld );

		}

		return result;

	}

	function projectObject( object, camera, shadowCamera ) {

		if ( object.visible === false ) return;

		var visible = ( object.layers.mask & camera.layers.mask ) !== 0;

		if ( visible && ( object.isMesh || object.isLine || object.isPoints ) ) {

			if ( object.castShadow && ( object.frustumCulled === false || _frustum.intersectsObject( object ) === true ) ) {

				var material = object.material;

				if ( material.visible === true ) {

					object.modelViewMatrix.multiplyMatrices( shadowCamera.matrixWorldInverse, object.matrixWorld );
					_renderList.push( object );

				}

			}

		}

		var children = object.children;

		for ( var i = 0, l = children.length; i < l; i ++ ) {

			projectObject( children[ i ], camera, shadowCamera );

		}

	}

}

/**
 * @author bhouston / http://clara.io
 */

function Ray( origin, direction ) {

	this.origin = ( origin !== undefined ) ? origin : new Vector3();
	this.direction = ( direction !== undefined ) ? direction : new Vector3();

}

Ray.prototype = {

	constructor: Ray,

	set: function ( origin, direction ) {

		this.origin.copy( origin );
		this.direction.copy( direction );

		return this;

	},

	clone: function () {

		return new this.constructor().copy( this );

	},

	copy: function ( ray ) {

		this.origin.copy( ray.origin );
		this.direction.copy( ray.direction );

		return this;

	},

	at: function ( t, optionalTarget ) {

		var result = optionalTarget || new Vector3();

		return result.copy( this.direction ).multiplyScalar( t ).add( this.origin );

	},

	lookAt: function ( v ) {

		this.direction.copy( v ).sub( this.origin ).normalize();

		return this;

	},

	recast: function () {

		var v1 = new Vector3();

		return function recast( t ) {

			this.origin.copy( this.at( t, v1 ) );

			return this;

		};

	}(),

	closestPointToPoint: function ( point, optionalTarget ) {

		var result = optionalTarget || new Vector3();
		result.subVectors( point, this.origin );
		var directionDistance = result.dot( this.direction );

		if ( directionDistance < 0 ) {

			return result.copy( this.origin );

		}

		return result.copy( this.direction ).multiplyScalar( directionDistance ).add( this.origin );

	},

	distanceToPoint: function ( point ) {

		return Math.sqrt( this.distanceSqToPoint( point ) );

	},

	distanceSqToPoint: function () {

		var v1 = new Vector3();

		return function distanceSqToPoint( point ) {

			var directionDistance = v1.subVectors( point, this.origin ).dot( this.direction );

			// point behind the ray

			if ( directionDistance < 0 ) {

				return this.origin.distanceToSquared( point );

			}

			v1.copy( this.direction ).multiplyScalar( directionDistance ).add( this.origin );

			return v1.distanceToSquared( point );

		};

	}(),

	distanceSqToSegment: function () {

		var segCenter = new Vector3();
		var segDir = new Vector3();
		var diff = new Vector3();

		return function distanceSqToSegment( v0, v1, optionalPointOnRay, optionalPointOnSegment ) {

			// from http://www.geometrictools.com/GTEngine/Include/Mathematics/GteDistRaySegment.h
			// It returns the min distance between the ray and the segment
			// defined by v0 and v1
			// It can also set two optional targets :
			// - The closest point on the ray
			// - The closest point on the segment

			segCenter.copy( v0 ).add( v1 ).multiplyScalar( 0.5 );
			segDir.copy( v1 ).sub( v0 ).normalize();
			diff.copy( this.origin ).sub( segCenter );

			var segExtent = v0.distanceTo( v1 ) * 0.5;
			var a01 = - this.direction.dot( segDir );
			var b0 = diff.dot( this.direction );
			var b1 = - diff.dot( segDir );
			var c = diff.lengthSq();
			var det = Math.abs( 1 - a01 * a01 );
			var s0, s1, sqrDist, extDet;

			if ( det > 0 ) {

				// The ray and segment are not parallel.

				s0 = a01 * b1 - b0;
				s1 = a01 * b0 - b1;
				extDet = segExtent * det;

				if ( s0 >= 0 ) {

					if ( s1 >= - extDet ) {

						if ( s1 <= extDet ) {

							// region 0
							// Minimum at interior points of ray and segment.

							var invDet = 1 / det;
							s0 *= invDet;
							s1 *= invDet;
							sqrDist = s0 * ( s0 + a01 * s1 + 2 * b0 ) + s1 * ( a01 * s0 + s1 + 2 * b1 ) + c;

						} else {

							// region 1

							s1 = segExtent;
							s0 = Math.max( 0, - ( a01 * s1 + b0 ) );
							sqrDist = - s0 * s0 + s1 * ( s1 + 2 * b1 ) + c;

						}

					} else {

						// region 5

						s1 = - segExtent;
						s0 = Math.max( 0, - ( a01 * s1 + b0 ) );
						sqrDist = - s0 * s0 + s1 * ( s1 + 2 * b1 ) + c;

					}

				} else {

					if ( s1 <= - extDet ) {

						// region 4

						s0 = Math.max( 0, - ( - a01 * segExtent + b0 ) );
						s1 = ( s0 > 0 ) ? - segExtent : Math.min( Math.max( - segExtent, - b1 ), segExtent );
						sqrDist = - s0 * s0 + s1 * ( s1 + 2 * b1 ) + c;

					} else if ( s1 <= extDet ) {

						// region 3

						s0 = 0;
						s1 = Math.min( Math.max( - segExtent, - b1 ), segExtent );
						sqrDist = s1 * ( s1 + 2 * b1 ) + c;

					} else {

						// region 2

						s0 = Math.max( 0, - ( a01 * segExtent + b0 ) );
						s1 = ( s0 > 0 ) ? segExtent : Math.min( Math.max( - segExtent, - b1 ), segExtent );
						sqrDist = - s0 * s0 + s1 * ( s1 + 2 * b1 ) + c;

					}

				}

			} else {

				// Ray and segment are parallel.

				s1 = ( a01 > 0 ) ? - segExtent : segExtent;
				s0 = Math.max( 0, - ( a01 * s1 + b0 ) );
				sqrDist = - s0 * s0 + s1 * ( s1 + 2 * b1 ) + c;

			}

			if ( optionalPointOnRay ) {

				optionalPointOnRay.copy( this.direction ).multiplyScalar( s0 ).add( this.origin );

			}

			if ( optionalPointOnSegment ) {

				optionalPointOnSegment.copy( segDir ).multiplyScalar( s1 ).add( segCenter );

			}

			return sqrDist;

		};

	}(),

	intersectSphere: function () {

		var v1 = new Vector3();

		return function intersectSphere( sphere, optionalTarget ) {

			v1.subVectors( sphere.center, this.origin );
			var tca = v1.dot( this.direction );
			var d2 = v1.dot( v1 ) - tca * tca;
			var radius2 = sphere.radius * sphere.radius;

			if ( d2 > radius2 ) return null;

			var thc = Math.sqrt( radius2 - d2 );

			// t0 = first intersect point - entrance on front of sphere
			var t0 = tca - thc;

			// t1 = second intersect point - exit point on back of sphere
			var t1 = tca + thc;

			// test to see if both t0 and t1 are behind the ray - if so, return null
			if ( t0 < 0 && t1 < 0 ) return null;

			// test to see if t0 is behind the ray:
			// if it is, the ray is inside the sphere, so return the second exit point scaled by t1,
			// in order to always return an intersect point that is in front of the ray.
			if ( t0 < 0 ) return this.at( t1, optionalTarget );

			// else t0 is in front of the ray, so return the first collision point scaled by t0
			return this.at( t0, optionalTarget );

		};

	}(),

	intersectsSphere: function ( sphere ) {

		return this.distanceToPoint( sphere.center ) <= sphere.radius;

	},

	distanceToPlane: function ( plane ) {

		var denominator = plane.normal.dot( this.direction );

		if ( denominator === 0 ) {

			// line is coplanar, return origin
			if ( plane.distanceToPoint( this.origin ) === 0 ) {

				return 0;

			}

			// Null is preferable to undefined since undefined means.... it is undefined

			return null;

		}

		var t = - ( this.origin.dot( plane.normal ) + plane.constant ) / denominator;

		// Return if the ray never intersects the plane

		return t >= 0 ? t :  null;

	},

	intersectPlane: function ( plane, optionalTarget ) {

		var t = this.distanceToPlane( plane );

		if ( t === null ) {

			return null;

		}

		return this.at( t, optionalTarget );

	},



	intersectsPlane: function ( plane ) {

		// check if the ray lies on the plane first

		var distToPoint = plane.distanceToPoint( this.origin );

		if ( distToPoint === 0 ) {

			return true;

		}

		var denominator = plane.normal.dot( this.direction );

		if ( denominator * distToPoint < 0 ) {

			return true;

		}

		// ray origin is behind the plane (and is pointing behind it)

		return false;

	},

	intersectBox: function ( box, optionalTarget ) {

		var tmin, tmax, tymin, tymax, tzmin, tzmax;

		var invdirx = 1 / this.direction.x,
			invdiry = 1 / this.direction.y,
			invdirz = 1 / this.direction.z;

		var origin = this.origin;

		if ( invdirx >= 0 ) {

			tmin = ( box.min.x - origin.x ) * invdirx;
			tmax = ( box.max.x - origin.x ) * invdirx;

		} else {

			tmin = ( box.max.x - origin.x ) * invdirx;
			tmax = ( box.min.x - origin.x ) * invdirx;

		}

		if ( invdiry >= 0 ) {

			tymin = ( box.min.y - origin.y ) * invdiry;
			tymax = ( box.max.y - origin.y ) * invdiry;

		} else {

			tymin = ( box.max.y - origin.y ) * invdiry;
			tymax = ( box.min.y - origin.y ) * invdiry;

		}

		if ( ( tmin > tymax ) || ( tymin > tmax ) ) return null;

		// These lines also handle the case where tmin or tmax is NaN
		// (result of 0 * Infinity). x !== x returns true if x is NaN

		if ( tymin > tmin || tmin !== tmin ) tmin = tymin;

		if ( tymax < tmax || tmax !== tmax ) tmax = tymax;

		if ( invdirz >= 0 ) {

			tzmin = ( box.min.z - origin.z ) * invdirz;
			tzmax = ( box.max.z - origin.z ) * invdirz;

		} else {

			tzmin = ( box.max.z - origin.z ) * invdirz;
			tzmax = ( box.min.z - origin.z ) * invdirz;

		}

		if ( ( tmin > tzmax ) || ( tzmin > tmax ) ) return null;

		if ( tzmin > tmin || tmin !== tmin ) tmin = tzmin;

		if ( tzmax < tmax || tmax !== tmax ) tmax = tzmax;

		//return point closest to the ray (positive side)

		if ( tmax < 0 ) return null;

		return this.at( tmin >= 0 ? tmin : tmax, optionalTarget );

	},

	intersectsBox: ( function () {

		var v = new Vector3();

		return function intersectsBox( box ) {

			return this.intersectBox( box, v ) !== null;

		};

	} )(),

	intersectTriangle: function () {

		// Compute the offset origin, edges, and normal.
		var diff = new Vector3();
		var edge1 = new Vector3();
		var edge2 = new Vector3();
		var normal = new Vector3();

		return function intersectTriangle( a, b, c, backfaceCulling, optionalTarget ) {

			// from http://www.geometrictools.com/GTEngine/Include/Mathematics/GteIntrRay3Triangle3.h

			edge1.subVectors( b, a );
			edge2.subVectors( c, a );
			normal.crossVectors( edge1, edge2 );

			// Solve Q + t*D = b1*E1 + b2*E2 (Q = kDiff, D = ray direction,
			// E1 = kEdge1, E2 = kEdge2, N = Cross(E1,E2)) by
			//   |Dot(D,N)|*b1 = sign(Dot(D,N))*Dot(D,Cross(Q,E2))
			//   |Dot(D,N)|*b2 = sign(Dot(D,N))*Dot(D,Cross(E1,Q))
			//   |Dot(D,N)|*t = -sign(Dot(D,N))*Dot(Q,N)
			var DdN = this.direction.dot( normal );
			var sign;

			if ( DdN > 0 ) {

				if ( backfaceCulling ) return null;
				sign = 1;

			} else if ( DdN < 0 ) {

				sign = - 1;
				DdN = - DdN;

			} else {

				return null;

			}

			diff.subVectors( this.origin, a );
			var DdQxE2 = sign * this.direction.dot( edge2.crossVectors( diff, edge2 ) );

			// b1 < 0, no intersection
			if ( DdQxE2 < 0 ) {

				return null;

			}

			var DdE1xQ = sign * this.direction.dot( edge1.cross( diff ) );

			// b2 < 0, no intersection
			if ( DdE1xQ < 0 ) {

				return null;

			}

			// b1+b2 > 1, no intersection
			if ( DdQxE2 + DdE1xQ > DdN ) {

				return null;

			}

			// Line intersects triangle, check if ray does.
			var QdN = - sign * diff.dot( normal );

			// t < 0, no intersection
			if ( QdN < 0 ) {

				return null;

			}

			// Ray intersects triangle.
			return this.at( QdN / DdN, optionalTarget );

		};

	}(),

	applyMatrix4: function ( matrix4 ) {

		this.direction.add( this.origin ).applyMatrix4( matrix4 );
		this.origin.applyMatrix4( matrix4 );
		this.direction.sub( this.origin );
		this.direction.normalize();

		return this;

	},

	equals: function ( ray ) {

		return ray.origin.equals( this.origin ) && ray.direction.equals( this.direction );

	}

};

/**
 * @author mrdoob / http://mrdoob.com/
 * @author WestLangley / http://github.com/WestLangley
 * @author bhouston / http://clara.io
 */

function Euler( x, y, z, order ) {

	this._x = x || 0;
	this._y = y || 0;
	this._z = z || 0;
	this._order = order || Euler.DefaultOrder;

}

Euler.RotationOrders = [ 'XYZ', 'YZX', 'ZXY', 'XZY', 'YXZ', 'ZYX' ];

Euler.DefaultOrder = 'XYZ';

Euler.prototype = {

	constructor: Euler,

	isEuler: true,

	get x () {

		return this._x;

	},

	set x ( value ) {

		this._x = value;
		this.onChangeCallback();

	},

	get y () {

		return this._y;

	},

	set y ( value ) {

		this._y = value;
		this.onChangeCallback();

	},

	get z () {

		return this._z;

	},

	set z ( value ) {

		this._z = value;
		this.onChangeCallback();

	},

	get order () {

		return this._order;

	},

	set order ( value ) {

		this._order = value;
		this.onChangeCallback();

	},

	set: function ( x, y, z, order ) {

		this._x = x;
		this._y = y;
		this._z = z;
		this._order = order || this._order;

		this.onChangeCallback();

		return this;

	},

	clone: function () {

		return new this.constructor( this._x, this._y, this._z, this._order );

	},

	copy: function ( euler ) {

		this._x = euler._x;
		this._y = euler._y;
		this._z = euler._z;
		this._order = euler._order;

		this.onChangeCallback();

		return this;

	},

	setFromRotationMatrix: function ( m, order, update ) {

		var clamp = _Math.clamp;

		// assumes the upper 3x3 of m is a pure rotation matrix (i.e, unscaled)

		var te = m.elements;
		var m11 = te[ 0 ], m12 = te[ 4 ], m13 = te[ 8 ];
		var m21 = te[ 1 ], m22 = te[ 5 ], m23 = te[ 9 ];
		var m31 = te[ 2 ], m32 = te[ 6 ], m33 = te[ 10 ];

		order = order || this._order;

		if ( order === 'XYZ' ) {

			this._y = Math.asin( clamp( m13, - 1, 1 ) );

			if ( Math.abs( m13 ) < 0.99999 ) {

				this._x = Math.atan2( - m23, m33 );
				this._z = Math.atan2( - m12, m11 );

			} else {

				this._x = Math.atan2( m32, m22 );
				this._z = 0;

			}

		} else if ( order === 'YXZ' ) {

			this._x = Math.asin( - clamp( m23, - 1, 1 ) );

			if ( Math.abs( m23 ) < 0.99999 ) {

				this._y = Math.atan2( m13, m33 );
				this._z = Math.atan2( m21, m22 );

			} else {

				this._y = Math.atan2( - m31, m11 );
				this._z = 0;

			}

		} else if ( order === 'ZXY' ) {

			this._x = Math.asin( clamp( m32, - 1, 1 ) );

			if ( Math.abs( m32 ) < 0.99999 ) {

				this._y = Math.atan2( - m31, m33 );
				this._z = Math.atan2( - m12, m22 );

			} else {

				this._y = 0;
				this._z = Math.atan2( m21, m11 );

			}

		} else if ( order === 'ZYX' ) {

			this._y = Math.asin( - clamp( m31, - 1, 1 ) );

			if ( Math.abs( m31 ) < 0.99999 ) {

				this._x = Math.atan2( m32, m33 );
				this._z = Math.atan2( m21, m11 );

			} else {

				this._x = 0;
				this._z = Math.atan2( - m12, m22 );

			}

		} else if ( order === 'YZX' ) {

			this._z = Math.asin( clamp( m21, - 1, 1 ) );

			if ( Math.abs( m21 ) < 0.99999 ) {

				this._x = Math.atan2( - m23, m22 );
				this._y = Math.atan2( - m31, m11 );

			} else {

				this._x = 0;
				this._y = Math.atan2( m13, m33 );

			}

		} else if ( order === 'XZY' ) {

			this._z = Math.asin( - clamp( m12, - 1, 1 ) );

			if ( Math.abs( m12 ) < 0.99999 ) {

				this._x = Math.atan2( m32, m22 );
				this._y = Math.atan2( m13, m11 );

			} else {

				this._x = Math.atan2( - m23, m33 );
				this._y = 0;

			}

		} else {

			console.warn( 'THREE.Euler: .setFromRotationMatrix() given unsupported order: ' + order );

		}

		this._order = order;

		if ( update !== false ) this.onChangeCallback();

		return this;

	},

	setFromQuaternion: function () {

		var matrix;

		return function setFromQuaternion( q, order, update ) {

			if ( matrix === undefined ) matrix = new Matrix4();

			matrix.makeRotationFromQuaternion( q );

			return this.setFromRotationMatrix( matrix, order, update );

		};

	}(),

	setFromVector3: function ( v, order ) {

		return this.set( v.x, v.y, v.z, order || this._order );

	},

	reorder: function () {

		// WARNING: this discards revolution information -bhouston

		var q = new Quaternion();

		return function reorder( newOrder ) {

			q.setFromEuler( this );

			return this.setFromQuaternion( q, newOrder );

		};

	}(),

	equals: function ( euler ) {

		return ( euler._x === this._x ) && ( euler._y === this._y ) && ( euler._z === this._z ) && ( euler._order === this._order );

	},

	fromArray: function ( array ) {

		this._x = array[ 0 ];
		this._y = array[ 1 ];
		this._z = array[ 2 ];
		if ( array[ 3 ] !== undefined ) this._order = array[ 3 ];

		this.onChangeCallback();

		return this;

	},

	toArray: function ( array, offset ) {

		if ( array === undefined ) array = [];
		if ( offset === undefined ) offset = 0;

		array[ offset ] = this._x;
		array[ offset + 1 ] = this._y;
		array[ offset + 2 ] = this._z;
		array[ offset + 3 ] = this._order;

		return array;

	},

	toVector3: function ( optionalResult ) {

		if ( optionalResult ) {

			return optionalResult.set( this._x, this._y, this._z );

		} else {

			return new Vector3( this._x, this._y, this._z );

		}

	},

	onChange: function ( callback ) {

		this.onChangeCallback = callback;

		return this;

	},

	onChangeCallback: function () {}

};

/**
 * @author mrdoob / http://mrdoob.com/
 */

function Layers() {

	this.mask = 1;

}

Layers.prototype = {

	constructor: Layers,

	set: function ( channel ) {

		this.mask = 1 << channel;

	},

	enable: function ( channel ) {

		this.mask |= 1 << channel;

	},

	toggle: function ( channel ) {

		this.mask ^= 1 << channel;

	},

	disable: function ( channel ) {

		this.mask &= ~ ( 1 << channel );

	},

	test: function ( layers ) {

		return ( this.mask & layers.mask ) !== 0;

	}

};

/**
 * @author mrdoob / http://mrdoob.com/
 * @author mikael emtinger / http://gomo.se/
 * @author alteredq / http://alteredqualia.com/
 * @author WestLangley / http://github.com/WestLangley
 * @author elephantatwork / www.elephantatwork.ch
 */

var object3DId = 0;

function Object3D() {

	Object.defineProperty( this, 'id', { value: object3DId ++ } );

	this.uuid = _Math.generateUUID();

	this.name = '';
	this.type = 'Object3D';

	this.parent = null;
	this.children = [];

	this.up = Object3D.DefaultUp.clone();

	var position = new Vector3();
	var rotation = new Euler();
	var quaternion = new Quaternion();
	var scale = new Vector3( 1, 1, 1 );

	function onRotationChange() {

		quaternion.setFromEuler( rotation, false );

	}

	function onQuaternionChange() {

		rotation.setFromQuaternion( quaternion, undefined, false );

	}

	rotation.onChange( onRotationChange );
	quaternion.onChange( onQuaternionChange );

	Object.defineProperties( this, {
		position: {
			enumerable: true,
			value: position
		},
		rotation: {
			enumerable: true,
			value: rotation
		},
		quaternion: {
			enumerable: true,
			value: quaternion
		},
		scale: {
			enumerable: true,
			value: scale
		},
		modelViewMatrix: {
			value: new Matrix4()
		},
		normalMatrix: {
			value: new Matrix3()
		}
	} );

	this.matrix = new Matrix4();
	this.matrixWorld = new Matrix4();

	this.matrixAutoUpdate = Object3D.DefaultMatrixAutoUpdate;
	this.matrixWorldNeedsUpdate = false;

	this.layers = new Layers();
	this.visible = true;

	this.castShadow = false;
	this.receiveShadow = false;

	this.frustumCulled = true;
	this.renderOrder = 0;

	this.userData = {};

	this.onBeforeRender = function () {};
	this.onAfterRender = function () {};

}

Object3D.DefaultUp = new Vector3( 0, 1, 0 );
Object3D.DefaultMatrixAutoUpdate = true;

Object3D.prototype = {

	constructor: Object3D,

	isObject3D: true,

	applyMatrix: function ( matrix ) {

		this.matrix.multiplyMatrices( matrix, this.matrix );

		this.matrix.decompose( this.position, this.quaternion, this.scale );

	},

	setRotationFromAxisAngle: function ( axis, angle ) {

		// assumes axis is normalized

		this.quaternion.setFromAxisAngle( axis, angle );

	},

	setRotationFromEuler: function ( euler ) {

		this.quaternion.setFromEuler( euler, true );

	},

	setRotationFromMatrix: function ( m ) {

		// assumes the upper 3x3 of m is a pure rotation matrix (i.e, unscaled)

		this.quaternion.setFromRotationMatrix( m );

	},

	setRotationFromQuaternion: function ( q ) {

		// assumes q is normalized

		this.quaternion.copy( q );

	},

	rotateOnAxis: function () {

		// rotate object on axis in object space
		// axis is assumed to be normalized

		var q1 = new Quaternion();

		return function rotateOnAxis( axis, angle ) {

			q1.setFromAxisAngle( axis, angle );

			this.quaternion.multiply( q1 );

			return this;

		};

	}(),

	rotateX: function () {

		var v1 = new Vector3( 1, 0, 0 );

		return function rotateX( angle ) {

			return this.rotateOnAxis( v1, angle );

		};

	}(),

	rotateY: function () {

		var v1 = new Vector3( 0, 1, 0 );

		return function rotateY( angle ) {

			return this.rotateOnAxis( v1, angle );

		};

	}(),

	rotateZ: function () {

		var v1 = new Vector3( 0, 0, 1 );

		return function rotateZ( angle ) {

			return this.rotateOnAxis( v1, angle );

		};

	}(),

	translateOnAxis: function () {

		// translate object by distance along axis in object space
		// axis is assumed to be normalized

		var v1 = new Vector3();

		return function translateOnAxis( axis, distance ) {

			v1.copy( axis ).applyQuaternion( this.quaternion );

			this.position.add( v1.multiplyScalar( distance ) );

			return this;

		};

	}(),

	translateX: function () {

		var v1 = new Vector3( 1, 0, 0 );

		return function translateX( distance ) {

			return this.translateOnAxis( v1, distance );

		};

	}(),

	translateY: function () {

		var v1 = new Vector3( 0, 1, 0 );

		return function translateY( distance ) {

			return this.translateOnAxis( v1, distance );

		};

	}(),

	translateZ: function () {

		var v1 = new Vector3( 0, 0, 1 );

		return function translateZ( distance ) {

			return this.translateOnAxis( v1, distance );

		};

	}(),

	localToWorld: function ( vector ) {

		return vector.applyMatrix4( this.matrixWorld );

	},

	worldToLocal: function () {

		var m1 = new Matrix4();

		return function worldToLocal( vector ) {

			return vector.applyMatrix4( m1.getInverse( this.matrixWorld ) );

		};

	}(),

	lookAt: function () {

		// This routine does not support objects with rotated and/or translated parent(s)

		var m1 = new Matrix4();

		return function lookAt( vector ) {

			m1.lookAt( vector, this.position, this.up );

			this.quaternion.setFromRotationMatrix( m1 );

		};

	}(),

	add: function ( object ) {

		if ( arguments.length > 1 ) {

			for ( var i = 0; i < arguments.length; i ++ ) {

				this.add( arguments[ i ] );

			}

			return this;

		}

		if ( object === this ) {

			console.error( "THREE.Object3D.add: object can't be added as a child of itself.", object );
			return this;

		}

		if ( ( object && object.isObject3D ) ) {

			if ( object.parent !== null ) {

				object.parent.remove( object );

			}

			object.parent = this;
			object.dispatchEvent( { type: 'added' } );

			this.children.push( object );

		} else {

			console.error( "THREE.Object3D.add: object not an instance of THREE.Object3D.", object );

		}

		return this;

	},

	remove: function ( object ) {

		if ( arguments.length > 1 ) {

			for ( var i = 0; i < arguments.length; i ++ ) {

				this.remove( arguments[ i ] );

			}

		}

		var index = this.children.indexOf( object );

		if ( index !== - 1 ) {

			object.parent = null;

			object.dispatchEvent( { type: 'removed' } );

			this.children.splice( index, 1 );

		}

	},

	getObjectById: function ( id ) {

		return this.getObjectByProperty( 'id', id );

	},

	getObjectByName: function ( name ) {

		return this.getObjectByProperty( 'name', name );

	},

	getObjectByProperty: function ( name, value ) {

		if ( this[ name ] === value ) return this;

		for ( var i = 0, l = this.children.length; i < l; i ++ ) {

			var child = this.children[ i ];
			var object = child.getObjectByProperty( name, value );

			if ( object !== undefined ) {

				return object;

			}

		}

		return undefined;

	},

	getWorldPosition: function ( optionalTarget ) {

		var result = optionalTarget || new Vector3();

		this.updateMatrixWorld( true );

		return result.setFromMatrixPosition( this.matrixWorld );

	},

	getWorldQuaternion: function () {

		var position = new Vector3();
		var scale = new Vector3();

		return function getWorldQuaternion( optionalTarget ) {

			var result = optionalTarget || new Quaternion();

			this.updateMatrixWorld( true );

			this.matrixWorld.decompose( position, result, scale );

			return result;

		};

	}(),

	getWorldRotation: function () {

		var quaternion = new Quaternion();

		return function getWorldRotation( optionalTarget ) {

			var result = optionalTarget || new Euler();

			this.getWorldQuaternion( quaternion );

			return result.setFromQuaternion( quaternion, this.rotation.order, false );

		};

	}(),

	getWorldScale: function () {

		var position = new Vector3();
		var quaternion = new Quaternion();

		return function getWorldScale( optionalTarget ) {

			var result = optionalTarget || new Vector3();

			this.updateMatrixWorld( true );

			this.matrixWorld.decompose( position, quaternion, result );

			return result;

		};

	}(),

	getWorldDirection: function () {

		var quaternion = new Quaternion();

		return function getWorldDirection( optionalTarget ) {

			var result = optionalTarget || new Vector3();

			this.getWorldQuaternion( quaternion );

			return result.set( 0, 0, 1 ).applyQuaternion( quaternion );

		};

	}(),

	raycast: function () {},

	traverse: function ( callback ) {

		callback( this );

		var children = this.children;

		for ( var i = 0, l = children.length; i < l; i ++ ) {

			children[ i ].traverse( callback );

		}

	},

	traverseVisible: function ( callback ) {

		if ( this.visible === false ) return;

		callback( this );

		var children = this.children;

		for ( var i = 0, l = children.length; i < l; i ++ ) {

			children[ i ].traverseVisible( callback );

		}

	},

	traverseAncestors: function ( callback ) {

		var parent = this.parent;

		if ( parent !== null ) {

			callback( parent );

			parent.traverseAncestors( callback );

		}

	},

	updateMatrix: function () {

		this.matrix.compose( this.position, this.quaternion, this.scale );

		this.matrixWorldNeedsUpdate = true;

	},

	updateMatrixWorld: function ( force ) {

		if ( this.matrixAutoUpdate === true ) this.updateMatrix();

		if ( this.matrixWorldNeedsUpdate === true || force === true ) {

			if ( this.parent === null ) {

				this.matrixWorld.copy( this.matrix );

			} else {

				this.matrixWorld.multiplyMatrices( this.parent.matrixWorld, this.matrix );

			}

			this.matrixWorldNeedsUpdate = false;

			force = true;

		}

		// update children

		var children = this.children;

		for ( var i = 0, l = children.length; i < l; i ++ ) {

			children[ i ].updateMatrixWorld( force );

		}

	},

	toJSON: function ( meta ) {

		// meta is '' when called from JSON.stringify
		var isRootObject = ( meta === undefined || meta === '' );

		var output = {};

		// meta is a hash used to collect geometries, materials.
		// not providing it implies that this is the root object
		// being serialized.
		if ( isRootObject ) {

			// initialize meta obj
			meta = {
				geometries: {},
				materials: {},
				textures: {},
				images: {}
			};

			output.metadata = {
				version: 4.4,
				type: 'Object',
				generator: 'Object3D.toJSON'
			};

		}

		// standard Object3D serialization

		var object = {};

		object.uuid = this.uuid;
		object.type = this.type;

		if ( this.name !== '' ) object.name = this.name;
		if ( JSON.stringify( this.userData ) !== '{}' ) object.userData = this.userData;
		if ( this.castShadow === true ) object.castShadow = true;
		if ( this.receiveShadow === true ) object.receiveShadow = true;
		if ( this.visible === false ) object.visible = false;

		object.matrix = this.matrix.toArray();

		//

		if ( this.geometry !== undefined ) {

			if ( meta.geometries[ this.geometry.uuid ] === undefined ) {

				meta.geometries[ this.geometry.uuid ] = this.geometry.toJSON( meta );

			}

			object.geometry = this.geometry.uuid;

		}

		if ( this.material !== undefined ) {

			if ( meta.materials[ this.material.uuid ] === undefined ) {

				meta.materials[ this.material.uuid ] = this.material.toJSON( meta );

			}

			object.material = this.material.uuid;

		}

		//

		if ( this.children.length > 0 ) {

			object.children = [];

			for ( var i = 0; i < this.children.length; i ++ ) {

				object.children.push( this.children[ i ].toJSON( meta ).object );

			}

		}

		if ( isRootObject ) {

			var geometries = extractFromCache( meta.geometries );
			var materials = extractFromCache( meta.materials );
			var textures = extractFromCache( meta.textures );
			var images = extractFromCache( meta.images );

			if ( geometries.length > 0 ) output.geometries = geometries;
			if ( materials.length > 0 ) output.materials = materials;
			if ( textures.length > 0 ) output.textures = textures;
			if ( images.length > 0 ) output.images = images;

		}

		output.object = object;

		return output;

		// extract data from the cache hash
		// remove metadata on each item
		// and return as array
		function extractFromCache( cache ) {

			var values = [];
			for ( var key in cache ) {

				var data = cache[ key ];
				delete data.metadata;
				values.push( data );

			}
			return values;

		}

	},

	clone: function ( recursive ) {

		return new this.constructor().copy( this, recursive );

	},

	copy: function ( source, recursive ) {

		if ( recursive === undefined ) recursive = true;

		this.name = source.name;

		this.up.copy( source.up );

		this.position.copy( source.position );
		this.quaternion.copy( source.quaternion );
		this.scale.copy( source.scale );

		this.matrix.copy( source.matrix );
		this.matrixWorld.copy( source.matrixWorld );

		this.matrixAutoUpdate = source.matrixAutoUpdate;
		this.matrixWorldNeedsUpdate = source.matrixWorldNeedsUpdate;

		this.layers.mask = source.layers.mask;
		this.visible = source.visible;

		this.castShadow = source.castShadow;
		this.receiveShadow = source.receiveShadow;

		this.frustumCulled = source.frustumCulled;
		this.renderOrder = source.renderOrder;

		this.userData = JSON.parse( JSON.stringify( source.userData ) );

		if ( recursive === true ) {

			for ( var i = 0; i < source.children.length; i ++ ) {

				var child = source.children[ i ];
				this.add( child.clone() );

			}

		}

		return this;

	}

};

Object.assign( Object3D.prototype, EventDispatcher.prototype );

/**
 * @author bhouston / http://clara.io
 */

function Line3( start, end ) {

	this.start = ( start !== undefined ) ? start : new Vector3();
	this.end = ( end !== undefined ) ? end : new Vector3();

}

Line3.prototype = {

	constructor: Line3,

	set: function ( start, end ) {

		this.start.copy( start );
		this.end.copy( end );

		return this;

	},

	clone: function () {

		return new this.constructor().copy( this );

	},

	copy: function ( line ) {

		this.start.copy( line.start );
		this.end.copy( line.end );

		return this;

	},

	getCenter: function ( optionalTarget ) {

		var result = optionalTarget || new Vector3();
		return result.addVectors( this.start, this.end ).multiplyScalar( 0.5 );

	},

	delta: function ( optionalTarget ) {

		var result = optionalTarget || new Vector3();
		return result.subVectors( this.end, this.start );

	},

	distanceSq: function () {

		return this.start.distanceToSquared( this.end );

	},

	distance: function () {

		return this.start.distanceTo( this.end );

	},

	at: function ( t, optionalTarget ) {

		var result = optionalTarget || new Vector3();

		return this.delta( result ).multiplyScalar( t ).add( this.start );

	},

	closestPointToPointParameter: function () {

		var startP = new Vector3();
		var startEnd = new Vector3();

		return function closestPointToPointParameter( point, clampToLine ) {

			startP.subVectors( point, this.start );
			startEnd.subVectors( this.end, this.start );

			var startEnd2 = startEnd.dot( startEnd );
			var startEnd_startP = startEnd.dot( startP );

			var t = startEnd_startP / startEnd2;

			if ( clampToLine ) {

				t = _Math.clamp( t, 0, 1 );

			}

			return t;

		};

	}(),

	closestPointToPoint: function ( point, clampToLine, optionalTarget ) {

		var t = this.closestPointToPointParameter( point, clampToLine );

		var result = optionalTarget || new Vector3();

		return this.delta( result ).multiplyScalar( t ).add( this.start );

	},

	applyMatrix4: function ( matrix ) {

		this.start.applyMatrix4( matrix );
		this.end.applyMatrix4( matrix );

		return this;

	},

	equals: function ( line ) {

		return line.start.equals( this.start ) && line.end.equals( this.end );

	}

};

/**
 * @author bhouston / http://clara.io
 * @author mrdoob / http://mrdoob.com/
 */

function Triangle( a, b, c ) {

	this.a = ( a !== undefined ) ? a : new Vector3();
	this.b = ( b !== undefined ) ? b : new Vector3();
	this.c = ( c !== undefined ) ? c : new Vector3();

}

Triangle.normal = function () {

	var v0 = new Vector3();

	return function normal( a, b, c, optionalTarget ) {

		var result = optionalTarget || new Vector3();

		result.subVectors( c, b );
		v0.subVectors( a, b );
		result.cross( v0 );

		var resultLengthSq = result.lengthSq();
		if ( resultLengthSq > 0 ) {

			return result.multiplyScalar( 1 / Math.sqrt( resultLengthSq ) );

		}

		return result.set( 0, 0, 0 );

	};

}();

// static/instance method to calculate barycentric coordinates
// based on: http://www.blackpawn.com/texts/pointinpoly/default.html
Triangle.barycoordFromPoint = function () {

	var v0 = new Vector3();
	var v1 = new Vector3();
	var v2 = new Vector3();

	return function barycoordFromPoint( point, a, b, c, optionalTarget ) {

		v0.subVectors( c, a );
		v1.subVectors( b, a );
		v2.subVectors( point, a );

		var dot00 = v0.dot( v0 );
		var dot01 = v0.dot( v1 );
		var dot02 = v0.dot( v2 );
		var dot11 = v1.dot( v1 );
		var dot12 = v1.dot( v2 );

		var denom = ( dot00 * dot11 - dot01 * dot01 );

		var result = optionalTarget || new Vector3();

		// collinear or singular triangle
		if ( denom === 0 ) {

			// arbitrary location outside of triangle?
			// not sure if this is the best idea, maybe should be returning undefined
			return result.set( - 2, - 1, - 1 );

		}

		var invDenom = 1 / denom;
		var u = ( dot11 * dot02 - dot01 * dot12 ) * invDenom;
		var v = ( dot00 * dot12 - dot01 * dot02 ) * invDenom;

		// barycentric coordinates must always sum to 1
		return result.set( 1 - u - v, v, u );

	};

}();

Triangle.containsPoint = function () {

	var v1 = new Vector3();

	return function containsPoint( point, a, b, c ) {

		var result = Triangle.barycoordFromPoint( point, a, b, c, v1 );

		return ( result.x >= 0 ) && ( result.y >= 0 ) && ( ( result.x + result.y ) <= 1 );

	};

}();

Triangle.prototype = {

	constructor: Triangle,

	set: function ( a, b, c ) {

		this.a.copy( a );
		this.b.copy( b );
		this.c.copy( c );

		return this;

	},

	setFromPointsAndIndices: function ( points, i0, i1, i2 ) {

		this.a.copy( points[ i0 ] );
		this.b.copy( points[ i1 ] );
		this.c.copy( points[ i2 ] );

		return this;

	},

	clone: function () {

		return new this.constructor().copy( this );

	},

	copy: function ( triangle ) {

		this.a.copy( triangle.a );
		this.b.copy( triangle.b );
		this.c.copy( triangle.c );

		return this;

	},

	area: function () {

		var v0 = new Vector3();
		var v1 = new Vector3();

		return function area() {

			v0.subVectors( this.c, this.b );
			v1.subVectors( this.a, this.b );

			return v0.cross( v1 ).length() * 0.5;

		};

	}(),

	midpoint: function ( optionalTarget ) {

		var result = optionalTarget || new Vector3();
		return result.addVectors( this.a, this.b ).add( this.c ).multiplyScalar( 1 / 3 );

	},

	normal: function ( optionalTarget ) {

		return Triangle.normal( this.a, this.b, this.c, optionalTarget );

	},

	plane: function ( optionalTarget ) {

		var result = optionalTarget || new Plane();

		return result.setFromCoplanarPoints( this.a, this.b, this.c );

	},

	barycoordFromPoint: function ( point, optionalTarget ) {

		return Triangle.barycoordFromPoint( point, this.a, this.b, this.c, optionalTarget );

	},

	containsPoint: function ( point ) {

		return Triangle.containsPoint( point, this.a, this.b, this.c );

	},

	closestPointToPoint: function () {

		var plane, edgeList, projectedPoint, closestPoint;

		return function closestPointToPoint( point, optionalTarget ) {

			if ( plane === undefined ) {

				plane = new Plane();
				edgeList = [ new Line3(), new Line3(), new Line3() ];
				projectedPoint = new Vector3();
				closestPoint = new Vector3();

			}

			var result = optionalTarget || new Vector3();
			var minDistance = Infinity;

			// project the point onto the plane of the triangle

			plane.setFromCoplanarPoints( this.a, this.b, this.c );
			plane.projectPoint( point, projectedPoint );

			// check if the projection lies within the triangle

			if( this.containsPoint( projectedPoint ) === true ) {

				// if so, this is the closest point

				result.copy( projectedPoint );

			} else {

				// if not, the point falls outside the triangle. the result is the closest point to the triangle's edges or vertices

				edgeList[ 0 ].set( this.a, this.b );
				edgeList[ 1 ].set( this.b, this.c );
				edgeList[ 2 ].set( this.c, this.a );

				for( var i = 0; i < edgeList.length; i ++ ) {

					edgeList[ i ].closestPointToPoint( projectedPoint, true, closestPoint );

					var distance = projectedPoint.distanceToSquared( closestPoint );

					if( distance < minDistance ) {

						minDistance = distance;

						result.copy( closestPoint );

					}

				}

			}

			return result;

		};

	}(),

	equals: function ( triangle ) {

		return triangle.a.equals( this.a ) && triangle.b.equals( this.b ) && triangle.c.equals( this.c );

	}

};

/**
 * @author mrdoob / http://mrdoob.com/
 * @author alteredq / http://alteredqualia.com/
 */

function Face3( a, b, c, normal, color, materialIndex ) {

	this.a = a;
	this.b = b;
	this.c = c;

	this.normal = (normal && normal.isVector3) ? normal : new Vector3();
	this.vertexNormals = Array.isArray( normal ) ? normal : [];

	this.color = (color && color.isColor) ? color : new Color();
	this.vertexColors = Array.isArray( color ) ? color : [];

	this.materialIndex = materialIndex !== undefined ? materialIndex : 0;

}

Face3.prototype = {

	constructor: Face3,

	clone: function () {

		return new this.constructor().copy( this );

	},

	copy: function ( source ) {

		this.a = source.a;
		this.b = source.b;
		this.c = source.c;

		this.normal.copy( source.normal );
		this.color.copy( source.color );

		this.materialIndex = source.materialIndex;

		for ( var i = 0, il = source.vertexNormals.length; i < il; i ++ ) {

			this.vertexNormals[ i ] = source.vertexNormals[ i ].clone();

		}

		for ( var i = 0, il = source.vertexColors.length; i < il; i ++ ) {

			this.vertexColors[ i ] = source.vertexColors[ i ].clone();

		}

		return this;

	}

};

/**
 * @author mrdoob / http://mrdoob.com/
 * @author alteredq / http://alteredqualia.com/
 *
 * parameters = {
 *  color: <hex>,
 *  opacity: <float>,
 *  map: new THREE.Texture( <Image> ),
 *
 *  lightMap: new THREE.Texture( <Image> ),
 *  lightMapIntensity: <float>
 *
 *  aoMap: new THREE.Texture( <Image> ),
 *  aoMapIntensity: <float>
 *
 *  specularMap: new THREE.Texture( <Image> ),
 *
 *  alphaMap: new THREE.Texture( <Image> ),
 *
 *  envMap: new THREE.TextureCube( [posx, negx, posy, negy, posz, negz] ),
 *  combine: THREE.Multiply,
 *  reflectivity: <float>,
 *  refractionRatio: <float>,
 *
 *  shading: THREE.SmoothShading,
 *  depthTest: <bool>,
 *  depthWrite: <bool>,
 *
 *  wireframe: <boolean>,
 *  wireframeLinewidth: <float>,
 *
 *  skinning: <bool>,
 *  morphTargets: <bool>
 * }
 */

function MeshBasicMaterial( parameters ) {

	Material.call( this );

	this.type = 'MeshBasicMaterial';

	this.color = new Color( 0xffffff ); // emissive

	this.map = null;

	this.lightMap = null;
	this.lightMapIntensity = 1.0;

	this.aoMap = null;
	this.aoMapIntensity = 1.0;

	this.specularMap = null;

	this.alphaMap = null;

	this.envMap = null;
	this.combine = MultiplyOperation;
	this.reflectivity = 1;
	this.refractionRatio = 0.98;

	this.wireframe = false;
	this.wireframeLinewidth = 1;
	this.wireframeLinecap = 'round';
	this.wireframeLinejoin = 'round';

	this.skinning = false;
	this.morphTargets = false;

	this.lights = false;

	this.setValues( parameters );

}

MeshBasicMaterial.prototype = Object.create( Material.prototype );
MeshBasicMaterial.prototype.constructor = MeshBasicMaterial;

MeshBasicMaterial.prototype.isMeshBasicMaterial = true;

MeshBasicMaterial.prototype.copy = function ( source ) {

	Material.prototype.copy.call( this, source );

	this.color.copy( source.color );

	this.map = source.map;

	this.lightMap = source.lightMap;
	this.lightMapIntensity = source.lightMapIntensity;

	this.aoMap = source.aoMap;
	this.aoMapIntensity = source.aoMapIntensity;

	this.specularMap = source.specularMap;

	this.alphaMap = source.alphaMap;

	this.envMap = source.envMap;
	this.combine = source.combine;
	this.reflectivity = source.reflectivity;
	this.refractionRatio = source.refractionRatio;

	this.wireframe = source.wireframe;
	this.wireframeLinewidth = source.wireframeLinewidth;
	this.wireframeLinecap = source.wireframeLinecap;
	this.wireframeLinejoin = source.wireframeLinejoin;

	this.skinning = source.skinning;
	this.morphTargets = source.morphTargets;

	return this;

};

/**
 * @author mrdoob / http://mrdoob.com/
 */

function BufferAttribute( array, itemSize, normalized ) {

	if ( Array.isArray( array ) ) {

		throw new TypeError( 'THREE.BufferAttribute: array should be a Typed Array.' );

	}

	this.uuid = _Math.generateUUID();

	this.array = array;
	this.itemSize = itemSize;
	this.count = array !== undefined ? array.length / itemSize : 0;
	this.normalized = normalized === true;

	this.dynamic = false;
	this.updateRange = { offset: 0, count: - 1 };

	this.onUploadCallback = function () {};

	this.version = 0;

}

BufferAttribute.prototype = {

	constructor: BufferAttribute,

	isBufferAttribute: true,

	set needsUpdate( value ) {

		if ( value === true ) this.version ++;

	},

	setArray: function ( array ) {

		if ( Array.isArray( array ) ) {

			throw new TypeError( 'THREE.BufferAttribute: array should be a Typed Array.' );

		}

		this.count = array !== undefined ? array.length / this.itemSize : 0;
		this.array = array;

	},

	setDynamic: function ( value ) {

		this.dynamic = value;

		return this;

	},

	copy: function ( source ) {

		this.array = new source.array.constructor( source.array );
		this.itemSize = source.itemSize;
		this.count = source.count;
		this.normalized = source.normalized;

		this.dynamic = source.dynamic;

		return this;

	},

	copyAt: function ( index1, attribute, index2 ) {

		index1 *= this.itemSize;
		index2 *= attribute.itemSize;

		for ( var i = 0, l = this.itemSize; i < l; i ++ ) {

			this.array[ index1 + i ] = attribute.array[ index2 + i ];

		}

		return this;

	},

	copyArray: function ( array ) {

		this.array.set( array );

		return this;

	},

	copyColorsArray: function ( colors ) {

		var array = this.array, offset = 0;

		for ( var i = 0, l = colors.length; i < l; i ++ ) {

			var color = colors[ i ];

			if ( color === undefined ) {

				console.warn( 'THREE.BufferAttribute.copyColorsArray(): color is undefined', i );
				color = new Color();

			}

			array[ offset ++ ] = color.r;
			array[ offset ++ ] = color.g;
			array[ offset ++ ] = color.b;

		}

		return this;

	},

	copyIndicesArray: function ( indices ) {

		var array = this.array, offset = 0;

		for ( var i = 0, l = indices.length; i < l; i ++ ) {

			var index = indices[ i ];

			array[ offset ++ ] = index.a;
			array[ offset ++ ] = index.b;
			array[ offset ++ ] = index.c;

		}

		return this;

	},

	copyVector2sArray: function ( vectors ) {

		var array = this.array, offset = 0;

		for ( var i = 0, l = vectors.length; i < l; i ++ ) {

			var vector = vectors[ i ];

			if ( vector === undefined ) {

				console.warn( 'THREE.BufferAttribute.copyVector2sArray(): vector is undefined', i );
				vector = new Vector2();

			}

			array[ offset ++ ] = vector.x;
			array[ offset ++ ] = vector.y;

		}

		return this;

	},

	copyVector3sArray: function ( vectors ) {

		var array = this.array, offset = 0;

		for ( var i = 0, l = vectors.length; i < l; i ++ ) {

			var vector = vectors[ i ];

			if ( vector === undefined ) {

				console.warn( 'THREE.BufferAttribute.copyVector3sArray(): vector is undefined', i );
				vector = new Vector3();

			}

			array[ offset ++ ] = vector.x;
			array[ offset ++ ] = vector.y;
			array[ offset ++ ] = vector.z;

		}

		return this;

	},

	copyVector4sArray: function ( vectors ) {

		var array = this.array, offset = 0;

		for ( var i = 0, l = vectors.length; i < l; i ++ ) {

			var vector = vectors[ i ];

			if ( vector === undefined ) {

				console.warn( 'THREE.BufferAttribute.copyVector4sArray(): vector is undefined', i );
				vector = new Vector4();

			}

			array[ offset ++ ] = vector.x;
			array[ offset ++ ] = vector.y;
			array[ offset ++ ] = vector.z;
			array[ offset ++ ] = vector.w;

		}

		return this;

	},

	set: function ( value, offset ) {

		if ( offset === undefined ) offset = 0;

		this.array.set( value, offset );

		return this;

	},

	getX: function ( index ) {

		return this.array[ index * this.itemSize ];

	},

	setX: function ( index, x ) {

		this.array[ index * this.itemSize ] = x;

		return this;

	},

	getY: function ( index ) {

		return this.array[ index * this.itemSize + 1 ];

	},

	setY: function ( index, y ) {

		this.array[ index * this.itemSize + 1 ] = y;

		return this;

	},

	getZ: function ( index ) {

		return this.array[ index * this.itemSize + 2 ];

	},

	setZ: function ( index, z ) {

		this.array[ index * this.itemSize + 2 ] = z;

		return this;

	},

	getW: function ( index ) {

		return this.array[ index * this.itemSize + 3 ];

	},

	setW: function ( index, w ) {

		this.array[ index * this.itemSize + 3 ] = w;

		return this;

	},

	setXY: function ( index, x, y ) {

		index *= this.itemSize;

		this.array[ index + 0 ] = x;
		this.array[ index + 1 ] = y;

		return this;

	},

	setXYZ: function ( index, x, y, z ) {

		index *= this.itemSize;

		this.array[ index + 0 ] = x;
		this.array[ index + 1 ] = y;
		this.array[ index + 2 ] = z;

		return this;

	},

	setXYZW: function ( index, x, y, z, w ) {

		index *= this.itemSize;

		this.array[ index + 0 ] = x;
		this.array[ index + 1 ] = y;
		this.array[ index + 2 ] = z;
		this.array[ index + 3 ] = w;

		return this;

	},

	onUpload: function ( callback ) {

		this.onUploadCallback = callback;

		return this;

	},

	clone: function () {

		return new this.constructor( this.array, this.itemSize ).copy( this );

	}

};

function Uint16BufferAttribute( array, itemSize ) {

	BufferAttribute.call( this, new Uint16Array( array ), itemSize );

}

Uint16BufferAttribute.prototype = Object.create( BufferAttribute.prototype );
Uint16BufferAttribute.prototype.constructor = Uint16BufferAttribute;


function Uint32BufferAttribute( array, itemSize ) {

	BufferAttribute.call( this, new Uint32Array( array ), itemSize );

}

Uint32BufferAttribute.prototype = Object.create( BufferAttribute.prototype );
Uint32BufferAttribute.prototype.constructor = Uint32BufferAttribute;


function Float32BufferAttribute( array, itemSize ) {

	BufferAttribute.call( this, new Float32Array( array ), itemSize );

}

Float32BufferAttribute.prototype = Object.create( BufferAttribute.prototype );
Float32BufferAttribute.prototype.constructor = Float32BufferAttribute;

/**
 * @author mrdoob / http://mrdoob.com/
 */

function DirectGeometry() {

	this.indices = [];
	this.vertices = [];
	this.normals = [];
	this.colors = [];
	this.uvs = [];
	this.uvs2 = [];

	this.groups = [];

	this.morphTargets = {};

	this.skinWeights = [];
	this.skinIndices = [];

	// this.lineDistances = [];

	this.boundingBox = null;
	this.boundingSphere = null;

	// update flags

	this.verticesNeedUpdate = false;
	this.normalsNeedUpdate = false;
	this.colorsNeedUpdate = false;
	this.uvsNeedUpdate = false;
	this.groupsNeedUpdate = false;

}

Object.assign( DirectGeometry.prototype, {

	computeGroups: function ( geometry ) {

		var group;
		var groups = [];
		var materialIndex = undefined;

		var faces = geometry.faces;

		for ( var i = 0; i < faces.length; i ++ ) {

			var face = faces[ i ];

			// materials

			if ( face.materialIndex !== materialIndex ) {

				materialIndex = face.materialIndex;

				if ( group !== undefined ) {

					group.count = ( i * 3 ) - group.start;
					groups.push( group );

				}

				group = {
					start: i * 3,
					materialIndex: materialIndex
				};

			}

		}

		if ( group !== undefined ) {

			group.count = ( i * 3 ) - group.start;
			groups.push( group );

		}

		this.groups = groups;

	},

	fromGeometry: function ( geometry ) {

		var faces = geometry.faces;
		var vertices = geometry.vertices;
		var faceVertexUvs = geometry.faceVertexUvs;

		var hasFaceVertexUv = faceVertexUvs[ 0 ] && faceVertexUvs[ 0 ].length > 0;
		var hasFaceVertexUv2 = faceVertexUvs[ 1 ] && faceVertexUvs[ 1 ].length > 0;

		// morphs

		var morphTargets = geometry.morphTargets;
		var morphTargetsLength = morphTargets.length;

		var morphTargetsPosition;

		if ( morphTargetsLength > 0 ) {

			morphTargetsPosition = [];

			for ( var i = 0; i < morphTargetsLength; i ++ ) {

				morphTargetsPosition[ i ] = [];

			}

			this.morphTargets.position = morphTargetsPosition;

		}

		var morphNormals = geometry.morphNormals;
		var morphNormalsLength = morphNormals.length;

		var morphTargetsNormal;

		if ( morphNormalsLength > 0 ) {

			morphTargetsNormal = [];

			for ( var i = 0; i < morphNormalsLength; i ++ ) {

				morphTargetsNormal[ i ] = [];

			}

			this.morphTargets.normal = morphTargetsNormal;

		}

		// skins

		var skinIndices = geometry.skinIndices;
		var skinWeights = geometry.skinWeights;

		var hasSkinIndices = skinIndices.length === vertices.length;
		var hasSkinWeights = skinWeights.length === vertices.length;

		//

		for ( var i = 0; i < faces.length; i ++ ) {

			var face = faces[ i ];

			this.vertices.push( vertices[ face.a ], vertices[ face.b ], vertices[ face.c ] );

			var vertexNormals = face.vertexNormals;

			if ( vertexNormals.length === 3 ) {

				this.normals.push( vertexNormals[ 0 ], vertexNormals[ 1 ], vertexNormals[ 2 ] );

			} else {

				var normal = face.normal;

				this.normals.push( normal, normal, normal );

			}

			var vertexColors = face.vertexColors;

			if ( vertexColors.length === 3 ) {

				this.colors.push( vertexColors[ 0 ], vertexColors[ 1 ], vertexColors[ 2 ] );

			} else {

				var color = face.color;

				this.colors.push( color, color, color );

			}

			if ( hasFaceVertexUv === true ) {

				var vertexUvs = faceVertexUvs[ 0 ][ i ];

				if ( vertexUvs !== undefined ) {

					this.uvs.push( vertexUvs[ 0 ], vertexUvs[ 1 ], vertexUvs[ 2 ] );

				} else {

					console.warn( 'THREE.DirectGeometry.fromGeometry(): Undefined vertexUv ', i );

					this.uvs.push( new Vector2(), new Vector2(), new Vector2() );

				}

			}

			if ( hasFaceVertexUv2 === true ) {

				var vertexUvs = faceVertexUvs[ 1 ][ i ];

				if ( vertexUvs !== undefined ) {

					this.uvs2.push( vertexUvs[ 0 ], vertexUvs[ 1 ], vertexUvs[ 2 ] );

				} else {

					console.warn( 'THREE.DirectGeometry.fromGeometry(): Undefined vertexUv2 ', i );

					this.uvs2.push( new Vector2(), new Vector2(), new Vector2() );

				}

			}

			// morphs

			for ( var j = 0; j < morphTargetsLength; j ++ ) {

				var morphTarget = morphTargets[ j ].vertices;

				morphTargetsPosition[ j ].push( morphTarget[ face.a ], morphTarget[ face.b ], morphTarget[ face.c ] );

			}

			for ( var j = 0; j < morphNormalsLength; j ++ ) {

				var morphNormal = morphNormals[ j ].vertexNormals[ i ];

				morphTargetsNormal[ j ].push( morphNormal.a, morphNormal.b, morphNormal.c );

			}

			// skins

			if ( hasSkinIndices ) {

				this.skinIndices.push( skinIndices[ face.a ], skinIndices[ face.b ], skinIndices[ face.c ] );

			}

			if ( hasSkinWeights ) {

				this.skinWeights.push( skinWeights[ face.a ], skinWeights[ face.b ], skinWeights[ face.c ] );

			}

		}

		this.computeGroups( geometry );

		this.verticesNeedUpdate = geometry.verticesNeedUpdate;
		this.normalsNeedUpdate = geometry.normalsNeedUpdate;
		this.colorsNeedUpdate = geometry.colorsNeedUpdate;
		this.uvsNeedUpdate = geometry.uvsNeedUpdate;
		this.groupsNeedUpdate = geometry.groupsNeedUpdate;

		return this;

	}

} );

// http://stackoverflow.com/questions/1669190/javascript-min-max-array-values/13440842#13440842

function arrayMax( array ) {

	var length = array.length, max = - Infinity;

	while ( length -- ) {

		if ( array[ length ] > max ) {

			max = array[ length ];

		}

	}

	return max;

}

/**
 * @author mrdoob / http://mrdoob.com/
 * @author kile / http://kile.stravaganza.org/
 * @author alteredq / http://alteredqualia.com/
 * @author mikael emtinger / http://gomo.se/
 * @author zz85 / http://www.lab4games.net/zz85/blog
 * @author bhouston / http://clara.io
 */

var count = 0;
function GeometryIdCount() { return count++; }

function Geometry() {

	Object.defineProperty( this, 'id', { value: GeometryIdCount() } );

	this.uuid = _Math.generateUUID();

	this.name = '';
	this.type = 'Geometry';

	this.vertices = [];
	this.colors = [];
	this.faces = [];
	this.faceVertexUvs = [[]];

	this.morphTargets = [];
	this.morphNormals = [];

	this.skinWeights = [];
	this.skinIndices = [];

	this.lineDistances = [];

	this.boundingBox = null;
	this.boundingSphere = null;

	// update flags

	this.elementsNeedUpdate = false;
	this.verticesNeedUpdate = false;
	this.uvsNeedUpdate = false;
	this.normalsNeedUpdate = false;
	this.colorsNeedUpdate = false;
	this.lineDistancesNeedUpdate = false;
	this.groupsNeedUpdate = false;

}

Geometry.prototype = {

	constructor: Geometry,

	isGeometry: true,

	applyMatrix: function ( matrix ) {

		var normalMatrix = new Matrix3().getNormalMatrix( matrix );

		for ( var i = 0, il = this.vertices.length; i < il; i ++ ) {

			var vertex = this.vertices[ i ];
			vertex.applyMatrix4( matrix );

		}

		for ( var i = 0, il = this.faces.length; i < il; i ++ ) {

			var face = this.faces[ i ];
			face.normal.applyMatrix3( normalMatrix ).normalize();

			for ( var j = 0, jl = face.vertexNormals.length; j < jl; j ++ ) {

				face.vertexNormals[ j ].applyMatrix3( normalMatrix ).normalize();

			}

		}

		if ( this.boundingBox !== null ) {

			this.computeBoundingBox();

		}

		if ( this.boundingSphere !== null ) {

			this.computeBoundingSphere();

		}

		this.verticesNeedUpdate = true;
		this.normalsNeedUpdate = true;

		return this;

	},

	rotateX: function () {

		// rotate geometry around world x-axis

		var m1;

		return function rotateX( angle ) {

			if ( m1 === undefined ) m1 = new Matrix4();

			m1.makeRotationX( angle );

			this.applyMatrix( m1 );

			return this;

		};

	}(),

	rotateY: function () {

		// rotate geometry around world y-axis

		var m1;

		return function rotateY( angle ) {

			if ( m1 === undefined ) m1 = new Matrix4();

			m1.makeRotationY( angle );

			this.applyMatrix( m1 );

			return this;

		};

	}(),

	rotateZ: function () {

		// rotate geometry around world z-axis

		var m1;

		return function rotateZ( angle ) {

			if ( m1 === undefined ) m1 = new Matrix4();

			m1.makeRotationZ( angle );

			this.applyMatrix( m1 );

			return this;

		};

	}(),

	translate: function () {

		// translate geometry

		var m1;

		return function translate( x, y, z ) {

			if ( m1 === undefined ) m1 = new Matrix4();

			m1.makeTranslation( x, y, z );

			this.applyMatrix( m1 );

			return this;

		};

	}(),

	scale: function () {

		// scale geometry

		var m1;

		return function scale( x, y, z ) {

			if ( m1 === undefined ) m1 = new Matrix4();

			m1.makeScale( x, y, z );

			this.applyMatrix( m1 );

			return this;

		};

	}(),

	lookAt: function () {

		var obj;

		return function lookAt( vector ) {

			if ( obj === undefined ) obj = new Object3D();

			obj.lookAt( vector );

			obj.updateMatrix();

			this.applyMatrix( obj.matrix );

		};

	}(),

	fromBufferGeometry: function ( geometry ) {

		var scope = this;

		var indices = geometry.index !== null ? geometry.index.array : undefined;
		var attributes = geometry.attributes;

		var positions = attributes.position.array;
		var normals = attributes.normal !== undefined ? attributes.normal.array : undefined;
		var colors = attributes.color !== undefined ? attributes.color.array : undefined;
		var uvs = attributes.uv !== undefined ? attributes.uv.array : undefined;
		var uvs2 = attributes.uv2 !== undefined ? attributes.uv2.array : undefined;

		if ( uvs2 !== undefined ) this.faceVertexUvs[ 1 ] = [];

		var tempNormals = [];
		var tempUVs = [];
		var tempUVs2 = [];

		for ( var i = 0, j = 0; i < positions.length; i += 3, j += 2 ) {

			scope.vertices.push( new Vector3( positions[ i ], positions[ i + 1 ], positions[ i + 2 ] ) );

			if ( normals !== undefined ) {

				tempNormals.push( new Vector3( normals[ i ], normals[ i + 1 ], normals[ i + 2 ] ) );

			}

			if ( colors !== undefined ) {

				scope.colors.push( new Color( colors[ i ], colors[ i + 1 ], colors[ i + 2 ] ) );

			}

			if ( uvs !== undefined ) {

				tempUVs.push( new Vector2( uvs[ j ], uvs[ j + 1 ] ) );

			}

			if ( uvs2 !== undefined ) {

				tempUVs2.push( new Vector2( uvs2[ j ], uvs2[ j + 1 ] ) );

			}

		}

		function addFace( a, b, c, materialIndex ) {

			var vertexNormals = normals !== undefined ? [ tempNormals[ a ].clone(), tempNormals[ b ].clone(), tempNormals[ c ].clone() ] : [];
			var vertexColors = colors !== undefined ? [ scope.colors[ a ].clone(), scope.colors[ b ].clone(), scope.colors[ c ].clone() ] : [];

			var face = new Face3( a, b, c, vertexNormals, vertexColors, materialIndex );

			scope.faces.push( face );

			if ( uvs !== undefined ) {

				scope.faceVertexUvs[ 0 ].push( [ tempUVs[ a ].clone(), tempUVs[ b ].clone(), tempUVs[ c ].clone() ] );

			}

			if ( uvs2 !== undefined ) {

				scope.faceVertexUvs[ 1 ].push( [ tempUVs2[ a ].clone(), tempUVs2[ b ].clone(), tempUVs2[ c ].clone() ] );

			}

		}

		if ( indices !== undefined ) {

			var groups = geometry.groups;

			if ( groups.length > 0 ) {

				for ( var i = 0; i < groups.length; i ++ ) {

					var group = groups[ i ];

					var start = group.start;
					var count = group.count;

					for ( var j = start, jl = start + count; j < jl; j += 3 ) {

						addFace( indices[ j ], indices[ j + 1 ], indices[ j + 2 ], group.materialIndex );

					}

				}

			} else {

				for ( var i = 0; i < indices.length; i += 3 ) {

					addFace( indices[ i ], indices[ i + 1 ], indices[ i + 2 ] );

				}

			}

		} else {

			for ( var i = 0; i < positions.length / 3; i += 3 ) {

				addFace( i, i + 1, i + 2 );

			}

		}

		this.computeFaceNormals();

		if ( geometry.boundingBox !== null ) {

			this.boundingBox = geometry.boundingBox.clone();

		}

		if ( geometry.boundingSphere !== null ) {

			this.boundingSphere = geometry.boundingSphere.clone();

		}

		return this;

	},

	center: function () {

		this.computeBoundingBox();

		var offset = this.boundingBox.getCenter().negate();

		this.translate( offset.x, offset.y, offset.z );

		return offset;

	},

	normalize: function () {

		this.computeBoundingSphere();

		var center = this.boundingSphere.center;
		var radius = this.boundingSphere.radius;

		var s = radius === 0 ? 1 : 1.0 / radius;

		var matrix = new Matrix4();
		matrix.set(
			s, 0, 0, - s * center.x,
			0, s, 0, - s * center.y,
			0, 0, s, - s * center.z,
			0, 0, 0, 1
		);

		this.applyMatrix( matrix );

		return this;

	},

	computeFaceNormals: function () {

		var cb = new Vector3(), ab = new Vector3();

		for ( var f = 0, fl = this.faces.length; f < fl; f ++ ) {

			var face = this.faces[ f ];

			var vA = this.vertices[ face.a ];
			var vB = this.vertices[ face.b ];
			var vC = this.vertices[ face.c ];

			cb.subVectors( vC, vB );
			ab.subVectors( vA, vB );
			cb.cross( ab );

			cb.normalize();

			face.normal.copy( cb );

		}

	},

	computeVertexNormals: function ( areaWeighted ) {

		if ( areaWeighted === undefined ) areaWeighted = true;

		var v, vl, f, fl, face, vertices;

		vertices = new Array( this.vertices.length );

		for ( v = 0, vl = this.vertices.length; v < vl; v ++ ) {

			vertices[ v ] = new Vector3();

		}

		if ( areaWeighted ) {

			// vertex normals weighted by triangle areas
			// http://www.iquilezles.org/www/articles/normals/normals.htm

			var vA, vB, vC;
			var cb = new Vector3(), ab = new Vector3();

			for ( f = 0, fl = this.faces.length; f < fl; f ++ ) {

				face = this.faces[ f ];

				vA = this.vertices[ face.a ];
				vB = this.vertices[ face.b ];
				vC = this.vertices[ face.c ];

				cb.subVectors( vC, vB );
				ab.subVectors( vA, vB );
				cb.cross( ab );

				vertices[ face.a ].add( cb );
				vertices[ face.b ].add( cb );
				vertices[ face.c ].add( cb );

			}

		} else {

			this.computeFaceNormals();

			for ( f = 0, fl = this.faces.length; f < fl; f ++ ) {

				face = this.faces[ f ];

				vertices[ face.a ].add( face.normal );
				vertices[ face.b ].add( face.normal );
				vertices[ face.c ].add( face.normal );

			}

		}

		for ( v = 0, vl = this.vertices.length; v < vl; v ++ ) {

			vertices[ v ].normalize();

		}

		for ( f = 0, fl = this.faces.length; f < fl; f ++ ) {

			face = this.faces[ f ];

			var vertexNormals = face.vertexNormals;

			if ( vertexNormals.length === 3 ) {

				vertexNormals[ 0 ].copy( vertices[ face.a ] );
				vertexNormals[ 1 ].copy( vertices[ face.b ] );
				vertexNormals[ 2 ].copy( vertices[ face.c ] );

			} else {

				vertexNormals[ 0 ] = vertices[ face.a ].clone();
				vertexNormals[ 1 ] = vertices[ face.b ].clone();
				vertexNormals[ 2 ] = vertices[ face.c ].clone();

			}

		}

		if ( this.faces.length > 0 ) {

			this.normalsNeedUpdate = true;

		}

	},

	computeFlatVertexNormals: function () {

		var f, fl, face;

		this.computeFaceNormals();

		for ( f = 0, fl = this.faces.length; f < fl; f ++ ) {

			face = this.faces[ f ];

			var vertexNormals = face.vertexNormals;

			if ( vertexNormals.length === 3 ) {

				vertexNormals[ 0 ].copy( face.normal );
				vertexNormals[ 1 ].copy( face.normal );
				vertexNormals[ 2 ].copy( face.normal );

			} else {

				vertexNormals[ 0 ] = face.normal.clone();
				vertexNormals[ 1 ] = face.normal.clone();
				vertexNormals[ 2 ] = face.normal.clone();

			}

		}

		if ( this.faces.length > 0 ) {

			this.normalsNeedUpdate = true;

		}

	},

	computeMorphNormals: function () {

		var i, il, f, fl, face;

		// save original normals
		// - create temp variables on first access
		//   otherwise just copy (for faster repeated calls)

		for ( f = 0, fl = this.faces.length; f < fl; f ++ ) {

			face = this.faces[ f ];

			if ( ! face.__originalFaceNormal ) {

				face.__originalFaceNormal = face.normal.clone();

			} else {

				face.__originalFaceNormal.copy( face.normal );

			}

			if ( ! face.__originalVertexNormals ) face.__originalVertexNormals = [];

			for ( i = 0, il = face.vertexNormals.length; i < il; i ++ ) {

				if ( ! face.__originalVertexNormals[ i ] ) {

					face.__originalVertexNormals[ i ] = face.vertexNormals[ i ].clone();

				} else {

					face.__originalVertexNormals[ i ].copy( face.vertexNormals[ i ] );

				}

			}

		}

		// use temp geometry to compute face and vertex normals for each morph

		var tmpGeo = new Geometry();
		tmpGeo.faces = this.faces;

		for ( i = 0, il = this.morphTargets.length; i < il; i ++ ) {

			// create on first access

			if ( ! this.morphNormals[ i ] ) {

				this.morphNormals[ i ] = {};
				this.morphNormals[ i ].faceNormals = [];
				this.morphNormals[ i ].vertexNormals = [];

				var dstNormalsFace = this.morphNormals[ i ].faceNormals;
				var dstNormalsVertex = this.morphNormals[ i ].vertexNormals;

				var faceNormal, vertexNormals;

				for ( f = 0, fl = this.faces.length; f < fl; f ++ ) {

					faceNormal = new Vector3();
					vertexNormals = { a: new Vector3(), b: new Vector3(), c: new Vector3() };

					dstNormalsFace.push( faceNormal );
					dstNormalsVertex.push( vertexNormals );

				}

			}

			var morphNormals = this.morphNormals[ i ];

			// set vertices to morph target

			tmpGeo.vertices = this.morphTargets[ i ].vertices;

			// compute morph normals

			tmpGeo.computeFaceNormals();
			tmpGeo.computeVertexNormals();

			// store morph normals

			var faceNormal, vertexNormals;

			for ( f = 0, fl = this.faces.length; f < fl; f ++ ) {

				face = this.faces[ f ];

				faceNormal = morphNormals.faceNormals[ f ];
				vertexNormals = morphNormals.vertexNormals[ f ];

				faceNormal.copy( face.normal );

				vertexNormals.a.copy( face.vertexNormals[ 0 ] );
				vertexNormals.b.copy( face.vertexNormals[ 1 ] );
				vertexNormals.c.copy( face.vertexNormals[ 2 ] );

			}

		}

		// restore original normals

		for ( f = 0, fl = this.faces.length; f < fl; f ++ ) {

			face = this.faces[ f ];

			face.normal = face.__originalFaceNormal;
			face.vertexNormals = face.__originalVertexNormals;

		}

	},

	computeLineDistances: function () {

		var d = 0;
		var vertices = this.vertices;

		for ( var i = 0, il = vertices.length; i < il; i ++ ) {

			if ( i > 0 ) {

				d += vertices[ i ].distanceTo( vertices[ i - 1 ] );

			}

			this.lineDistances[ i ] = d;

		}

	},

	computeBoundingBox: function () {

		if ( this.boundingBox === null ) {

			this.boundingBox = new Box3();

		}

		this.boundingBox.setFromPoints( this.vertices );

	},

	computeBoundingSphere: function () {

		if ( this.boundingSphere === null ) {

			this.boundingSphere = new Sphere();

		}

		this.boundingSphere.setFromPoints( this.vertices );

	},

	merge: function ( geometry, matrix, materialIndexOffset ) {

		if ( ( geometry && geometry.isGeometry ) === false ) {

			console.error( 'THREE.Geometry.merge(): geometry not an instance of THREE.Geometry.', geometry );
			return;

		}

		var normalMatrix,
		vertexOffset = this.vertices.length,
		vertices1 = this.vertices,
		vertices2 = geometry.vertices,
		faces1 = this.faces,
		faces2 = geometry.faces,
		uvs1 = this.faceVertexUvs[ 0 ],
		uvs2 = geometry.faceVertexUvs[ 0 ],
		colors1 = this.colors,
		colors2 = geometry.colors;

		if ( materialIndexOffset === undefined ) materialIndexOffset = 0;

		if ( matrix !== undefined ) {

			normalMatrix = new Matrix3().getNormalMatrix( matrix );

		}

		// vertices

		for ( var i = 0, il = vertices2.length; i < il; i ++ ) {

			var vertex = vertices2[ i ];

			var vertexCopy = vertex.clone();

			if ( matrix !== undefined ) vertexCopy.applyMatrix4( matrix );

			vertices1.push( vertexCopy );

		}

		// colors

		for ( var i = 0, il = colors2.length; i < il; i ++ ) {

			colors1.push( colors2[ i ].clone() );

		}

		// faces

		for ( i = 0, il = faces2.length; i < il; i ++ ) {

			var face = faces2[ i ], faceCopy, normal, color,
			faceVertexNormals = face.vertexNormals,
			faceVertexColors = face.vertexColors;

			faceCopy = new Face3( face.a + vertexOffset, face.b + vertexOffset, face.c + vertexOffset );
			faceCopy.normal.copy( face.normal );

			if ( normalMatrix !== undefined ) {

				faceCopy.normal.applyMatrix3( normalMatrix ).normalize();

			}

			for ( var j = 0, jl = faceVertexNormals.length; j < jl; j ++ ) {

				normal = faceVertexNormals[ j ].clone();

				if ( normalMatrix !== undefined ) {

					normal.applyMatrix3( normalMatrix ).normalize();

				}

				faceCopy.vertexNormals.push( normal );

			}

			faceCopy.color.copy( face.color );

			for ( var j = 0, jl = faceVertexColors.length; j < jl; j ++ ) {

				color = faceVertexColors[ j ];
				faceCopy.vertexColors.push( color.clone() );

			}

			faceCopy.materialIndex = face.materialIndex + materialIndexOffset;

			faces1.push( faceCopy );

		}

		// uvs

		for ( i = 0, il = uvs2.length; i < il; i ++ ) {

			var uv = uvs2[ i ], uvCopy = [];

			if ( uv === undefined ) {

				continue;

			}

			for ( var j = 0, jl = uv.length; j < jl; j ++ ) {

				uvCopy.push( uv[ j ].clone() );

			}

			uvs1.push( uvCopy );

		}

	},

	mergeMesh: function ( mesh ) {

		if ( ( mesh && mesh.isMesh ) === false ) {

			console.error( 'THREE.Geometry.mergeMesh(): mesh not an instance of THREE.Mesh.', mesh );
			return;

		}

		mesh.matrixAutoUpdate && mesh.updateMatrix();

		this.merge( mesh.geometry, mesh.matrix );

	},

	/*
	 * Checks for duplicate vertices with hashmap.
	 * Duplicated vertices are removed
	 * and faces' vertices are updated.
	 */

	mergeVertices: function () {

		var verticesMap = {}; // Hashmap for looking up vertices by position coordinates (and making sure they are unique)
		var unique = [], changes = [];

		var v, key;
		var precisionPoints = 4; // number of decimal points, e.g. 4 for epsilon of 0.0001
		var precision = Math.pow( 10, precisionPoints );
		var i, il, face;
		var indices, j, jl;

		for ( i = 0, il = this.vertices.length; i < il; i ++ ) {

			v = this.vertices[ i ];
			key = Math.round( v.x * precision ) + '_' + Math.round( v.y * precision ) + '_' + Math.round( v.z * precision );

			if ( verticesMap[ key ] === undefined ) {

				verticesMap[ key ] = i;
				unique.push( this.vertices[ i ] );
				changes[ i ] = unique.length - 1;

			} else {

				//console.log('Duplicate vertex found. ', i, ' could be using ', verticesMap[key]);
				changes[ i ] = changes[ verticesMap[ key ] ];

			}

		}


		// if faces are completely degenerate after merging vertices, we
		// have to remove them from the geometry.
		var faceIndicesToRemove = [];

		for ( i = 0, il = this.faces.length; i < il; i ++ ) {

			face = this.faces[ i ];

			face.a = changes[ face.a ];
			face.b = changes[ face.b ];
			face.c = changes[ face.c ];

			indices = [ face.a, face.b, face.c ];

			// if any duplicate vertices are found in a Face3
			// we have to remove the face as nothing can be saved
			for ( var n = 0; n < 3; n ++ ) {

				if ( indices[ n ] === indices[ ( n + 1 ) % 3 ] ) {

					faceIndicesToRemove.push( i );
					break;

				}

			}

		}

		for ( i = faceIndicesToRemove.length - 1; i >= 0; i -- ) {

			var idx = faceIndicesToRemove[ i ];

			this.faces.splice( idx, 1 );

			for ( j = 0, jl = this.faceVertexUvs.length; j < jl; j ++ ) {

				this.faceVertexUvs[ j ].splice( idx, 1 );

			}

		}

		// Use unique set of vertices

		var diff = this.vertices.length - unique.length;
		this.vertices = unique;
		return diff;

	},

	sortFacesByMaterialIndex: function () {

		var faces = this.faces;
		var length = faces.length;

		// tag faces

		for ( var i = 0; i < length; i ++ ) {

			faces[ i ]._id = i;

		}

		// sort faces

		function materialIndexSort( a, b ) {

			return a.materialIndex - b.materialIndex;

		}

		faces.sort( materialIndexSort );

		// sort uvs

		var uvs1 = this.faceVertexUvs[ 0 ];
		var uvs2 = this.faceVertexUvs[ 1 ];

		var newUvs1, newUvs2;

		if ( uvs1 && uvs1.length === length ) newUvs1 = [];
		if ( uvs2 && uvs2.length === length ) newUvs2 = [];

		for ( var i = 0; i < length; i ++ ) {

			var id = faces[ i ]._id;

			if ( newUvs1 ) newUvs1.push( uvs1[ id ] );
			if ( newUvs2 ) newUvs2.push( uvs2[ id ] );

		}

		if ( newUvs1 ) this.faceVertexUvs[ 0 ] = newUvs1;
		if ( newUvs2 ) this.faceVertexUvs[ 1 ] = newUvs2;

	},

	toJSON: function () {

		var data = {
			metadata: {
				version: 4.4,
				type: 'Geometry',
				generator: 'Geometry.toJSON'
			}
		};

		// standard Geometry serialization

		data.uuid = this.uuid;
		data.type = this.type;
		if ( this.name !== '' ) data.name = this.name;

		if ( this.parameters !== undefined ) {

			var parameters = this.parameters;

			for ( var key in parameters ) {

				if ( parameters[ key ] !== undefined ) data[ key ] = parameters[ key ];

			}

			return data;

		}

		var vertices = [];

		for ( var i = 0; i < this.vertices.length; i ++ ) {

			var vertex = this.vertices[ i ];
			vertices.push( vertex.x, vertex.y, vertex.z );

		}

		var faces = [];
		var normals = [];
		var normalsHash = {};
		var colors = [];
		var colorsHash = {};
		var uvs = [];
		var uvsHash = {};

		for ( var i = 0; i < this.faces.length; i ++ ) {

			var face = this.faces[ i ];

			var hasMaterial = true;
			var hasFaceUv = false; // deprecated
			var hasFaceVertexUv = this.faceVertexUvs[ 0 ][ i ] !== undefined;
			var hasFaceNormal = face.normal.length() > 0;
			var hasFaceVertexNormal = face.vertexNormals.length > 0;
			var hasFaceColor = face.color.r !== 1 || face.color.g !== 1 || face.color.b !== 1;
			var hasFaceVertexColor = face.vertexColors.length > 0;

			var faceType = 0;

			faceType = setBit( faceType, 0, 0 ); // isQuad
			faceType = setBit( faceType, 1, hasMaterial );
			faceType = setBit( faceType, 2, hasFaceUv );
			faceType = setBit( faceType, 3, hasFaceVertexUv );
			faceType = setBit( faceType, 4, hasFaceNormal );
			faceType = setBit( faceType, 5, hasFaceVertexNormal );
			faceType = setBit( faceType, 6, hasFaceColor );
			faceType = setBit( faceType, 7, hasFaceVertexColor );

			faces.push( faceType );
			faces.push( face.a, face.b, face.c );
			faces.push( face.materialIndex );

			if ( hasFaceVertexUv ) {

				var faceVertexUvs = this.faceVertexUvs[ 0 ][ i ];

				faces.push(
					getUvIndex( faceVertexUvs[ 0 ] ),
					getUvIndex( faceVertexUvs[ 1 ] ),
					getUvIndex( faceVertexUvs[ 2 ] )
				);

			}

			if ( hasFaceNormal ) {

				faces.push( getNormalIndex( face.normal ) );

			}

			if ( hasFaceVertexNormal ) {

				var vertexNormals = face.vertexNormals;

				faces.push(
					getNormalIndex( vertexNormals[ 0 ] ),
					getNormalIndex( vertexNormals[ 1 ] ),
					getNormalIndex( vertexNormals[ 2 ] )
				);

			}

			if ( hasFaceColor ) {

				faces.push( getColorIndex( face.color ) );

			}

			if ( hasFaceVertexColor ) {

				var vertexColors = face.vertexColors;

				faces.push(
					getColorIndex( vertexColors[ 0 ] ),
					getColorIndex( vertexColors[ 1 ] ),
					getColorIndex( vertexColors[ 2 ] )
				);

			}

		}

		function setBit( value, position, enabled ) {

			return enabled ? value | ( 1 << position ) : value & ( ~ ( 1 << position ) );

		}

		function getNormalIndex( normal ) {

			var hash = normal.x.toString() + normal.y.toString() + normal.z.toString();

			if ( normalsHash[ hash ] !== undefined ) {

				return normalsHash[ hash ];

			}

			normalsHash[ hash ] = normals.length / 3;
			normals.push( normal.x, normal.y, normal.z );

			return normalsHash[ hash ];

		}

		function getColorIndex( color ) {

			var hash = color.r.toString() + color.g.toString() + color.b.toString();

			if ( colorsHash[ hash ] !== undefined ) {

				return colorsHash[ hash ];

			}

			colorsHash[ hash ] = colors.length;
			colors.push( color.getHex() );

			return colorsHash[ hash ];

		}

		function getUvIndex( uv ) {

			var hash = uv.x.toString() + uv.y.toString();

			if ( uvsHash[ hash ] !== undefined ) {

				return uvsHash[ hash ];

			}

			uvsHash[ hash ] = uvs.length / 2;
			uvs.push( uv.x, uv.y );

			return uvsHash[ hash ];

		}

		data.data = {};

		data.data.vertices = vertices;
		data.data.normals = normals;
		if ( colors.length > 0 ) data.data.colors = colors;
		if ( uvs.length > 0 ) data.data.uvs = [ uvs ]; // temporal backward compatibility
		data.data.faces = faces;

		return data;

	},

	clone: function () {

		/*
		// Handle primitives

		var parameters = this.parameters;

		if ( parameters !== undefined ) {

			var values = [];

			for ( var key in parameters ) {

				values.push( parameters[ key ] );

			}

			var geometry = Object.create( this.constructor.prototype );
			this.constructor.apply( geometry, values );
			return geometry;

		}

		return new this.constructor().copy( this );
		*/

		return new Geometry().copy( this );

	},

	copy: function ( source ) {

		var i, il, j, jl, k, kl;

		// reset

		this.vertices = [];
		this.colors = [];
		this.faces = [];
		this.faceVertexUvs = [[]];
		this.morphTargets = [];
		this.morphNormals = [];
		this.skinWeights = [];
		this.skinIndices = [];
		this.lineDistances = [];
		this.boundingBox = null;
		this.boundingSphere = null;

		// name

		this.name = source.name;

		// vertices

		var vertices = source.vertices;

		for ( i = 0, il = vertices.length; i < il; i ++ ) {

			this.vertices.push( vertices[ i ].clone() );

		}

		// colors

		var colors = source.colors;

		for ( i = 0, il = colors.length; i < il; i ++ ) {

			this.colors.push( colors[ i ].clone() );

		}

		// faces

		var faces = source.faces;

		for ( i = 0, il = faces.length; i < il; i ++ ) {

			this.faces.push( faces[ i ].clone() );

		}

		// face vertex uvs

		for ( i = 0, il = source.faceVertexUvs.length; i < il; i ++ ) {

			var faceVertexUvs = source.faceVertexUvs[ i ];

			if ( this.faceVertexUvs[ i ] === undefined ) {

				this.faceVertexUvs[ i ] = [];

			}

			for ( j = 0, jl = faceVertexUvs.length; j < jl; j ++ ) {

				var uvs = faceVertexUvs[ j ], uvsCopy = [];

				for ( k = 0, kl = uvs.length; k < kl; k ++ ) {

					var uv = uvs[ k ];

					uvsCopy.push( uv.clone() );

				}

				this.faceVertexUvs[ i ].push( uvsCopy );

			}

		}

		// morph targets

		var morphTargets = source.morphTargets;

		for ( i = 0, il = morphTargets.length; i < il; i ++ ) {

			var morphTarget = {};
			morphTarget.name = morphTargets[ i ].name;

			// vertices

			if ( morphTargets[ i ].vertices !== undefined ) {

				morphTarget.vertices = [];

				for ( j = 0, jl = morphTargets[ i ].vertices.length; j < jl; j ++ ) {

					morphTarget.vertices.push( morphTargets[ i ].vertices[ j ].clone() );

				}

			}

			// normals

			if ( morphTargets[ i ].normals !== undefined ) {

				morphTarget.normals = [];

				for ( j = 0, jl = morphTargets[ i ].normals.length; j < jl; j ++ ) {

					morphTarget.normals.push( morphTargets[ i ].normals[ j ].clone() );

				}

			}

			this.morphTargets.push( morphTarget );

		}

		// morph normals

		var morphNormals = source.morphNormals;

		for ( i = 0, il = morphNormals.length; i < il; i ++ ) {

			var morphNormal = {};

			// vertex normals

			if ( morphNormals[ i ].vertexNormals !== undefined ) {

				morphNormal.vertexNormals = [];

				for ( j = 0, jl = morphNormals[ i ].vertexNormals.length; j < jl; j ++ ) {

					var srcVertexNormal = morphNormals[ i ].vertexNormals[ j ];
					var destVertexNormal = {};

					destVertexNormal.a = srcVertexNormal.a.clone();
					destVertexNormal.b = srcVertexNormal.b.clone();
					destVertexNormal.c = srcVertexNormal.c.clone();

					morphNormal.vertexNormals.push( destVertexNormal );

				}

			}

			// face normals

			if ( morphNormals[ i ].faceNormals !== undefined ) {

				morphNormal.faceNormals = [];

				for ( j = 0, jl = morphNormals[ i ].faceNormals.length; j < jl; j ++ ) {

					morphNormal.faceNormals.push( morphNormals[ i ].faceNormals[ j ].clone() );

				}

			}

			this.morphNormals.push( morphNormal );

		}

		// skin weights

		var skinWeights = source.skinWeights;

		for ( i = 0, il = skinWeights.length; i < il; i ++ ) {

			this.skinWeights.push( skinWeights[ i ].clone() );

		}

		// skin indices

		var skinIndices = source.skinIndices;

		for ( i = 0, il = skinIndices.length; i < il; i ++ ) {

			this.skinIndices.push( skinIndices[ i ].clone() );

		}

		// line distances

		var lineDistances = source.lineDistances;

		for ( i = 0, il = lineDistances.length; i < il; i ++ ) {

			this.lineDistances.push( lineDistances[ i ] );

		}

		// bounding box

		var boundingBox = source.boundingBox;

		if ( boundingBox !== null ) {

			this.boundingBox = boundingBox.clone();

		}

		// bounding sphere

		var boundingSphere = source.boundingSphere;

		if ( boundingSphere !== null ) {

			this.boundingSphere = boundingSphere.clone();

		}

		// update flags

		this.elementsNeedUpdate = source.elementsNeedUpdate;
		this.verticesNeedUpdate = source.verticesNeedUpdate;
		this.uvsNeedUpdate = source.uvsNeedUpdate;
		this.normalsNeedUpdate = source.normalsNeedUpdate;
		this.colorsNeedUpdate = source.colorsNeedUpdate;
		this.lineDistancesNeedUpdate = source.lineDistancesNeedUpdate;
		this.groupsNeedUpdate = source.groupsNeedUpdate;

		return this;

	},

	dispose: function () {

		this.dispatchEvent( { type: 'dispose' } );

	}

};

Object.assign( Geometry.prototype, EventDispatcher.prototype );

/**
 * @author alteredq / http://alteredqualia.com/
 * @author mrdoob / http://mrdoob.com/
 */

function BufferGeometry() {

	Object.defineProperty( this, 'id', { value: GeometryIdCount() } );

	this.uuid = _Math.generateUUID();

	this.name = '';
	this.type = 'BufferGeometry';

	this.index = null;
	this.attributes = {};

	this.morphAttributes = {};

	this.groups = [];

	this.boundingBox = null;
	this.boundingSphere = null;

	this.drawRange = { start: 0, count: Infinity };

}

BufferGeometry.prototype = {

	constructor: BufferGeometry,

	isBufferGeometry: true,

	getIndex: function () {

		return this.index;

	},

	setIndex: function ( index ) {

		if ( Array.isArray( index ) ) {

			this.index = new ( arrayMax( index ) > 65535 ? Uint32BufferAttribute : Uint16BufferAttribute )( index, 1 );

		} else {

			this.index = index;

		}

	},

	addAttribute: function ( name, attribute ) {

		if ( ( attribute && attribute.isBufferAttribute ) === false && ( attribute && attribute.isInterleavedBufferAttribute ) === false ) {

			console.warn( 'THREE.BufferGeometry: .addAttribute() now expects ( name, attribute ).' );

			this.addAttribute( name, new BufferAttribute( arguments[ 1 ], arguments[ 2 ] ) );

			return;

		}

		if ( name === 'index' ) {

			console.warn( 'THREE.BufferGeometry.addAttribute: Use .setIndex() for index attribute.' );
			this.setIndex( attribute );

			return;

		}

		this.attributes[ name ] = attribute;

		return this;

	},

	getAttribute: function ( name ) {

		return this.attributes[ name ];

	},

	removeAttribute: function ( name ) {

		delete this.attributes[ name ];

		return this;

	},

	addGroup: function ( start, count, materialIndex ) {

		this.groups.push( {

			start: start,
			count: count,
			materialIndex: materialIndex !== undefined ? materialIndex : 0

		} );

	},

	clearGroups: function () {

		this.groups = [];

	},

	setDrawRange: function ( start, count ) {

		this.drawRange.start = start;
		this.drawRange.count = count;

	},

	applyMatrix: function ( matrix ) {

		var position = this.attributes.position;

		if ( position !== undefined ) {

			matrix.applyToBufferAttribute( position );
			position.needsUpdate = true;

		}

		var normal = this.attributes.normal;

		if ( normal !== undefined ) {

			var normalMatrix = new Matrix3().getNormalMatrix( matrix );

			normalMatrix.applyToBufferAttribute( normal );
			normal.needsUpdate = true;

		}

		if ( this.boundingBox !== null ) {

			this.computeBoundingBox();

		}

		if ( this.boundingSphere !== null ) {

			this.computeBoundingSphere();

		}

		return this;

	},

	rotateX: function () {

		// rotate geometry around world x-axis

		var m1;

		return function rotateX( angle ) {

			if ( m1 === undefined ) m1 = new Matrix4();

			m1.makeRotationX( angle );

			this.applyMatrix( m1 );

			return this;

		};

	}(),

	rotateY: function () {

		// rotate geometry around world y-axis

		var m1;

		return function rotateY( angle ) {

			if ( m1 === undefined ) m1 = new Matrix4();

			m1.makeRotationY( angle );

			this.applyMatrix( m1 );

			return this;

		};

	}(),

	rotateZ: function () {

		// rotate geometry around world z-axis

		var m1;

		return function rotateZ( angle ) {

			if ( m1 === undefined ) m1 = new Matrix4();

			m1.makeRotationZ( angle );

			this.applyMatrix( m1 );

			return this;

		};

	}(),

	translate: function () {

		// translate geometry

		var m1;

		return function translate( x, y, z ) {

			if ( m1 === undefined ) m1 = new Matrix4();

			m1.makeTranslation( x, y, z );

			this.applyMatrix( m1 );

			return this;

		};

	}(),

	scale: function () {

		// scale geometry

		var m1;

		return function scale( x, y, z ) {

			if ( m1 === undefined ) m1 = new Matrix4();

			m1.makeScale( x, y, z );

			this.applyMatrix( m1 );

			return this;

		};

	}(),

	lookAt: function () {

		var obj;

		return function lookAt( vector ) {

			if ( obj === undefined ) obj = new Object3D();

			obj.lookAt( vector );

			obj.updateMatrix();

			this.applyMatrix( obj.matrix );

		};

	}(),

	center: function () {

		this.computeBoundingBox();

		var offset = this.boundingBox.getCenter().negate();

		this.translate( offset.x, offset.y, offset.z );

		return offset;

	},

	setFromObject: function ( object ) {

		// console.log( 'THREE.BufferGeometry.setFromObject(). Converting', object, this );

		var geometry = object.geometry;

		if ( object.isPoints || object.isLine ) {

			var positions = new Float32BufferAttribute( geometry.vertices.length * 3, 3 );
			var colors = new Float32BufferAttribute( geometry.colors.length * 3, 3 );

			this.addAttribute( 'position', positions.copyVector3sArray( geometry.vertices ) );
			this.addAttribute( 'color', colors.copyColorsArray( geometry.colors ) );

			if ( geometry.lineDistances && geometry.lineDistances.length === geometry.vertices.length ) {

				var lineDistances = new Float32BufferAttribute( geometry.lineDistances.length, 1 );

				this.addAttribute( 'lineDistance', lineDistances.copyArray( geometry.lineDistances ) );

			}

			if ( geometry.boundingSphere !== null ) {

				this.boundingSphere = geometry.boundingSphere.clone();

			}

			if ( geometry.boundingBox !== null ) {

				this.boundingBox = geometry.boundingBox.clone();

			}

		} else if ( object.isMesh ) {

			if ( geometry && geometry.isGeometry ) {

				this.fromGeometry( geometry );

			}

		}

		return this;

	},

	updateFromObject: function ( object ) {

		var geometry = object.geometry;

		if ( object.isMesh ) {

			var direct = geometry.__directGeometry;

			if ( geometry.elementsNeedUpdate === true ) {

				direct = undefined;
				geometry.elementsNeedUpdate = false;

			}

			if ( direct === undefined ) {

				return this.fromGeometry( geometry );

			}

			direct.verticesNeedUpdate = geometry.verticesNeedUpdate;
			direct.normalsNeedUpdate = geometry.normalsNeedUpdate;
			direct.colorsNeedUpdate = geometry.colorsNeedUpdate;
			direct.uvsNeedUpdate = geometry.uvsNeedUpdate;
			direct.groupsNeedUpdate = geometry.groupsNeedUpdate;

			geometry.verticesNeedUpdate = false;
			geometry.normalsNeedUpdate = false;
			geometry.colorsNeedUpdate = false;
			geometry.uvsNeedUpdate = false;
			geometry.groupsNeedUpdate = false;

			geometry = direct;

		}

		var attribute;

		if ( geometry.verticesNeedUpdate === true ) {

			attribute = this.attributes.position;

			if ( attribute !== undefined ) {

				attribute.copyVector3sArray( geometry.vertices );
				attribute.needsUpdate = true;

			}

			geometry.verticesNeedUpdate = false;

		}

		if ( geometry.normalsNeedUpdate === true ) {

			attribute = this.attributes.normal;

			if ( attribute !== undefined ) {

				attribute.copyVector3sArray( geometry.normals );
				attribute.needsUpdate = true;

			}

			geometry.normalsNeedUpdate = false;

		}

		if ( geometry.colorsNeedUpdate === true ) {

			attribute = this.attributes.color;

			if ( attribute !== undefined ) {

				attribute.copyColorsArray( geometry.colors );
				attribute.needsUpdate = true;

			}

			geometry.colorsNeedUpdate = false;

		}

		if ( geometry.uvsNeedUpdate ) {

			attribute = this.attributes.uv;

			if ( attribute !== undefined ) {

				attribute.copyVector2sArray( geometry.uvs );
				attribute.needsUpdate = true;

			}

			geometry.uvsNeedUpdate = false;

		}

		if ( geometry.lineDistancesNeedUpdate ) {

			attribute = this.attributes.lineDistance;

			if ( attribute !== undefined ) {

				attribute.copyArray( geometry.lineDistances );
				attribute.needsUpdate = true;

			}

			geometry.lineDistancesNeedUpdate = false;

		}

		if ( geometry.groupsNeedUpdate ) {

			geometry.computeGroups( object.geometry );
			this.groups = geometry.groups;

			geometry.groupsNeedUpdate = false;

		}

		return this;

	},

	fromGeometry: function ( geometry ) {

		geometry.__directGeometry = new DirectGeometry().fromGeometry( geometry );

		return this.fromDirectGeometry( geometry.__directGeometry );

	},

	fromDirectGeometry: function ( geometry ) {

		var positions = new Float32Array( geometry.vertices.length * 3 );
		this.addAttribute( 'position', new BufferAttribute( positions, 3 ).copyVector3sArray( geometry.vertices ) );

		if ( geometry.normals.length > 0 ) {

			var normals = new Float32Array( geometry.normals.length * 3 );
			this.addAttribute( 'normal', new BufferAttribute( normals, 3 ).copyVector3sArray( geometry.normals ) );

		}

		if ( geometry.colors.length > 0 ) {

			var colors = new Float32Array( geometry.colors.length * 3 );
			this.addAttribute( 'color', new BufferAttribute( colors, 3 ).copyColorsArray( geometry.colors ) );

		}

		if ( geometry.uvs.length > 0 ) {

			var uvs = new Float32Array( geometry.uvs.length * 2 );
			this.addAttribute( 'uv', new BufferAttribute( uvs, 2 ).copyVector2sArray( geometry.uvs ) );

		}

		if ( geometry.uvs2.length > 0 ) {

			var uvs2 = new Float32Array( geometry.uvs2.length * 2 );
			this.addAttribute( 'uv2', new BufferAttribute( uvs2, 2 ).copyVector2sArray( geometry.uvs2 ) );

		}

		if ( geometry.indices.length > 0 ) {

			var TypeArray = arrayMax( geometry.indices ) > 65535 ? Uint32Array : Uint16Array;
			var indices = new TypeArray( geometry.indices.length * 3 );
			this.setIndex( new BufferAttribute( indices, 1 ).copyIndicesArray( geometry.indices ) );

		}

		// groups

		this.groups = geometry.groups;

		// morphs

		for ( var name in geometry.morphTargets ) {

			var array = [];
			var morphTargets = geometry.morphTargets[ name ];

			for ( var i = 0, l = morphTargets.length; i < l; i ++ ) {

				var morphTarget = morphTargets[ i ];

				var attribute = new Float32BufferAttribute( morphTarget.length * 3, 3 );

				array.push( attribute.copyVector3sArray( morphTarget ) );

			}

			this.morphAttributes[ name ] = array;

		}

		// skinning

		if ( geometry.skinIndices.length > 0 ) {

			var skinIndices = new Float32BufferAttribute( geometry.skinIndices.length * 4, 4 );
			this.addAttribute( 'skinIndex', skinIndices.copyVector4sArray( geometry.skinIndices ) );

		}

		if ( geometry.skinWeights.length > 0 ) {

			var skinWeights = new Float32BufferAttribute( geometry.skinWeights.length * 4, 4 );
			this.addAttribute( 'skinWeight', skinWeights.copyVector4sArray( geometry.skinWeights ) );

		}

		//

		if ( geometry.boundingSphere !== null ) {

			this.boundingSphere = geometry.boundingSphere.clone();

		}

		if ( geometry.boundingBox !== null ) {

			this.boundingBox = geometry.boundingBox.clone();

		}

		return this;

	},

	computeBoundingBox: function () {

		if ( this.boundingBox === null ) {

			this.boundingBox = new Box3();

		}

		var position = this.attributes.position;

		if ( position !== undefined ) {

			this.boundingBox.setFromBufferAttribute( position );

		} else {

			this.boundingBox.makeEmpty();

		}

		if ( isNaN( this.boundingBox.min.x ) || isNaN( this.boundingBox.min.y ) || isNaN( this.boundingBox.min.z ) ) {

			console.error( 'THREE.BufferGeometry.computeBoundingBox: Computed min/max have NaN values. The "position" attribute is likely to have NaN values.', this );

		}

	},

	computeBoundingSphere: function () {

		var box = new Box3();
		var vector = new Vector3();

		return function computeBoundingSphere() {

			if ( this.boundingSphere === null ) {

				this.boundingSphere = new Sphere();

			}

			var position = this.attributes.position;

			if ( position ) {

				var center = this.boundingSphere.center;

				box.setFromBufferAttribute( position );
				box.getCenter( center );

				// hoping to find a boundingSphere with a radius smaller than the
				// boundingSphere of the boundingBox: sqrt(3) smaller in the best case

				var maxRadiusSq = 0;

				for ( var i = 0, il = position.count; i < il; i ++ ) {

					vector.x = position.getX( i );
					vector.y = position.getY( i );
					vector.z = position.getZ( i );
					maxRadiusSq = Math.max( maxRadiusSq, center.distanceToSquared( vector ) );

				}

				this.boundingSphere.radius = Math.sqrt( maxRadiusSq );

				if ( isNaN( this.boundingSphere.radius ) ) {

					console.error( 'THREE.BufferGeometry.computeBoundingSphere(): Computed radius is NaN. The "position" attribute is likely to have NaN values.', this );

				}

			}

		};

	}(),

	computeFaceNormals: function () {

		// backwards compatibility

	},

	computeVertexNormals: function () {

		var index = this.index;
		var attributes = this.attributes;
		var groups = this.groups;

		if ( attributes.position ) {

			var positions = attributes.position.array;

			if ( attributes.normal === undefined ) {

				this.addAttribute( 'normal', new BufferAttribute( new Float32Array( positions.length ), 3 ) );

			} else {

				// reset existing normals to zero

				var array = attributes.normal.array;

				for ( var i = 0, il = array.length; i < il; i ++ ) {

					array[ i ] = 0;

				}

			}

			var normals = attributes.normal.array;

			var vA, vB, vC;
			var pA = new Vector3(), pB = new Vector3(), pC = new Vector3();
			var cb = new Vector3(), ab = new Vector3();

			// indexed elements

			if ( index ) {

				var indices = index.array;

				if ( groups.length === 0 ) {

					this.addGroup( 0, indices.length );

				}

				for ( var j = 0, jl = groups.length; j < jl; ++ j ) {

					var group = groups[ j ];

					var start = group.start;
					var count = group.count;

					for ( var i = start, il = start + count; i < il; i += 3 ) {

						vA = indices[ i + 0 ] * 3;
						vB = indices[ i + 1 ] * 3;
						vC = indices[ i + 2 ] * 3;

						pA.fromArray( positions, vA );
						pB.fromArray( positions, vB );
						pC.fromArray( positions, vC );

						cb.subVectors( pC, pB );
						ab.subVectors( pA, pB );
						cb.cross( ab );

						normals[ vA ] += cb.x;
						normals[ vA + 1 ] += cb.y;
						normals[ vA + 2 ] += cb.z;

						normals[ vB ] += cb.x;
						normals[ vB + 1 ] += cb.y;
						normals[ vB + 2 ] += cb.z;

						normals[ vC ] += cb.x;
						normals[ vC + 1 ] += cb.y;
						normals[ vC + 2 ] += cb.z;

					}

				}

			} else {

				// non-indexed elements (unconnected triangle soup)

				for ( var i = 0, il = positions.length; i < il; i += 9 ) {

					pA.fromArray( positions, i );
					pB.fromArray( positions, i + 3 );
					pC.fromArray( positions, i + 6 );

					cb.subVectors( pC, pB );
					ab.subVectors( pA, pB );
					cb.cross( ab );

					normals[ i ] = cb.x;
					normals[ i + 1 ] = cb.y;
					normals[ i + 2 ] = cb.z;

					normals[ i + 3 ] = cb.x;
					normals[ i + 4 ] = cb.y;
					normals[ i + 5 ] = cb.z;

					normals[ i + 6 ] = cb.x;
					normals[ i + 7 ] = cb.y;
					normals[ i + 8 ] = cb.z;

				}

			}

			this.normalizeNormals();

			attributes.normal.needsUpdate = true;

		}

	},

	merge: function ( geometry, offset ) {

		if ( ( geometry && geometry.isBufferGeometry ) === false ) {

			console.error( 'THREE.BufferGeometry.merge(): geometry not an instance of THREE.BufferGeometry.', geometry );
			return;

		}

		if ( offset === undefined ) offset = 0;

		var attributes = this.attributes;

		for ( var key in attributes ) {

			if ( geometry.attributes[ key ] === undefined ) continue;

			var attribute1 = attributes[ key ];
			var attributeArray1 = attribute1.array;

			var attribute2 = geometry.attributes[ key ];
			var attributeArray2 = attribute2.array;

			var attributeSize = attribute2.itemSize;

			for ( var i = 0, j = attributeSize * offset; i < attributeArray2.length; i ++, j ++ ) {

				attributeArray1[ j ] = attributeArray2[ i ];

			}

		}

		return this;

	},

	normalizeNormals: function () {

		var normals = this.attributes.normal.array;

		var x, y, z, n;

		for ( var i = 0, il = normals.length; i < il; i += 3 ) {

			x = normals[ i ];
			y = normals[ i + 1 ];
			z = normals[ i + 2 ];

			n = 1.0 / Math.sqrt( x * x + y * y + z * z );

			normals[ i ] *= n;
			normals[ i + 1 ] *= n;
			normals[ i + 2 ] *= n;

		}

	},

	toNonIndexed: function () {

		if ( this.index === null ) {

			console.warn( 'THREE.BufferGeometry.toNonIndexed(): Geometry is already non-indexed.' );
			return this;

		}

		var geometry2 = new BufferGeometry();

		var indices = this.index.array;
		var attributes = this.attributes;

		for ( var name in attributes ) {

			var attribute = attributes[ name ];

			var array = attribute.array;
			var itemSize = attribute.itemSize;

			var array2 = new array.constructor( indices.length * itemSize );

			var index = 0, index2 = 0;

			for ( var i = 0, l = indices.length; i < l; i ++ ) {

				index = indices[ i ] * itemSize;

				for ( var j = 0; j < itemSize; j ++ ) {

					array2[ index2 ++ ] = array[ index ++ ];

				}

			}

			geometry2.addAttribute( name, new BufferAttribute( array2, itemSize ) );

		}

		return geometry2;

	},

	toJSON: function () {

		var data = {
			metadata: {
				version: 4.4,
				type: 'BufferGeometry',
				generator: 'BufferGeometry.toJSON'
			}
		};

		// standard BufferGeometry serialization

		data.uuid = this.uuid;
		data.type = this.type;
		if ( this.name !== '' ) data.name = this.name;

		if ( this.parameters !== undefined ) {

			var parameters = this.parameters;

			for ( var key in parameters ) {

				if ( parameters[ key ] !== undefined ) data[ key ] = parameters[ key ];

			}

			return data;

		}

		data.data = { attributes: {} };

		var index = this.index;

		if ( index !== null ) {

			var array = Array.prototype.slice.call( index.array );

			data.data.index = {
				type: index.array.constructor.name,
				array: array
			};

		}

		var attributes = this.attributes;

		for ( var key in attributes ) {

			var attribute = attributes[ key ];

			var array = Array.prototype.slice.call( attribute.array );

			data.data.attributes[ key ] = {
				itemSize: attribute.itemSize,
				type: attribute.array.constructor.name,
				array: array,
				normalized: attribute.normalized
			};

		}

		var groups = this.groups;

		if ( groups.length > 0 ) {

			data.data.groups = JSON.parse( JSON.stringify( groups ) );

		}

		var boundingSphere = this.boundingSphere;

		if ( boundingSphere !== null ) {

			data.data.boundingSphere = {
				center: boundingSphere.center.toArray(),
				radius: boundingSphere.radius
			};

		}

		return data;

	},

	clone: function () {

		/*
		// Handle primitives

		var parameters = this.parameters;

		if ( parameters !== undefined ) {

			var values = [];

			for ( var key in parameters ) {

				values.push( parameters[ key ] );

			}

			var geometry = Object.create( this.constructor.prototype );
			this.constructor.apply( geometry, values );
			return geometry;

		}

		return new this.constructor().copy( this );
		*/

		return new BufferGeometry().copy( this );

	},

	copy: function ( source ) {

		var name, i, l;

		// reset

		this.index = null;
		this.attributes = {};
		this.morphAttributes = {};
		this.groups = [];
		this.boundingBox = null;
		this.boundingSphere = null;

		// name

		this.name = source.name;

		// index

		var index = source.index;

		if ( index !== null ) {

			this.setIndex( index.clone() );

		}

		// attributes

		var attributes = source.attributes;

		for ( name in attributes ) {

			var attribute = attributes[ name ];
			this.addAttribute( name, attribute.clone() );

		}

		// morph attributes

		var morphAttributes = source.morphAttributes;

		for ( name in morphAttributes ) {

			var array = [];
			var morphAttribute = morphAttributes[ name ]; // morphAttribute: array of Float32BufferAttributes

			for ( i = 0, l = morphAttribute.length; i < l; i ++ ) {

				array.push( morphAttribute[ i ].clone() );

			}

			this.morphAttributes[ name ] = array;

		}

		// groups

		var groups = source.groups;

		for ( i = 0, l = groups.length; i < l; i ++ ) {

			var group = groups[ i ];
			this.addGroup( group.start, group.count, group.materialIndex );

		}

		// bounding box

		var boundingBox = source.boundingBox;

		if ( boundingBox !== null ) {

			this.boundingBox = boundingBox.clone();

		}

		// bounding sphere

		var boundingSphere = source.boundingSphere;

		if ( boundingSphere !== null ) {

			this.boundingSphere = boundingSphere.clone();

		}

		// draw range

		this.drawRange.start = source.drawRange.start;
		this.drawRange.count = source.drawRange.count;

		return this;

	},

	dispose: function () {

		this.dispatchEvent( { type: 'dispose' } );

	}

};

BufferGeometry.MaxIndex = 65535;

Object.assign( BufferGeometry.prototype, EventDispatcher.prototype );

/**
 * @author mrdoob / http://mrdoob.com/
 * @author alteredq / http://alteredqualia.com/
 * @author mikael emtinger / http://gomo.se/
 * @author jonobr1 / http://jonobr1.com/
 */

function Mesh( geometry, material ) {

	Object3D.call( this );

	this.type = 'Mesh';

	this.geometry = geometry !== undefined ? geometry : new BufferGeometry();
	this.material = material !== undefined ? material : new MeshBasicMaterial( { color: Math.random() * 0xffffff } );

	this.drawMode = TrianglesDrawMode;

	this.updateMorphTargets();

}

Mesh.prototype = Object.assign( Object.create( Object3D.prototype ), {

	constructor: Mesh,

	isMesh: true,

	setDrawMode: function ( value ) {

		this.drawMode = value;

	},

	copy: function ( source ) {

		Object3D.prototype.copy.call( this, source );

		this.drawMode = source.drawMode;

		return this;

	},

	updateMorphTargets: function () {

		var morphTargets = this.geometry.morphTargets;

		if ( morphTargets !== undefined && morphTargets.length > 0 ) {

			this.morphTargetInfluences = [];
			this.morphTargetDictionary = {};

			for ( var m = 0, ml = morphTargets.length; m < ml; m ++ ) {

				this.morphTargetInfluences.push( 0 );
				this.morphTargetDictionary[ morphTargets[ m ].name ] = m;

			}

		}

	},

	raycast: ( function () {

		var inverseMatrix = new Matrix4();
		var ray = new Ray();
		var sphere = new Sphere();

		var vA = new Vector3();
		var vB = new Vector3();
		var vC = new Vector3();

		var tempA = new Vector3();
		var tempB = new Vector3();
		var tempC = new Vector3();

		var uvA = new Vector2();
		var uvB = new Vector2();
		var uvC = new Vector2();

		var barycoord = new Vector3();

		var intersectionPoint = new Vector3();
		var intersectionPointWorld = new Vector3();

		function uvIntersection( point, p1, p2, p3, uv1, uv2, uv3 ) {

			Triangle.barycoordFromPoint( point, p1, p2, p3, barycoord );

			uv1.multiplyScalar( barycoord.x );
			uv2.multiplyScalar( barycoord.y );
			uv3.multiplyScalar( barycoord.z );

			uv1.add( uv2 ).add( uv3 );

			return uv1.clone();

		}

		function checkIntersection( object, raycaster, ray, pA, pB, pC, point ) {

			var intersect;
			var material = object.material;

			if ( material.side === BackSide ) {

				intersect = ray.intersectTriangle( pC, pB, pA, true, point );

			} else {

				intersect = ray.intersectTriangle( pA, pB, pC, material.side !== DoubleSide, point );

			}

			if ( intersect === null ) return null;

			intersectionPointWorld.copy( point );
			intersectionPointWorld.applyMatrix4( object.matrixWorld );

			var distance = raycaster.ray.origin.distanceTo( intersectionPointWorld );

			if ( distance < raycaster.near || distance > raycaster.far ) return null;

			return {
				distance: distance,
				point: intersectionPointWorld.clone(),
				object: object
			};

		}

		function checkBufferGeometryIntersection( object, raycaster, ray, position, uv, a, b, c ) {

			vA.fromBufferAttribute( position, a );
			vB.fromBufferAttribute( position, b );
			vC.fromBufferAttribute( position, c );

			var intersection = checkIntersection( object, raycaster, ray, vA, vB, vC, intersectionPoint );

			if ( intersection ) {

				if ( uv ) {

					uvA.fromBufferAttribute( uv, a );
					uvB.fromBufferAttribute( uv, b );
					uvC.fromBufferAttribute( uv, c );

					intersection.uv = uvIntersection( intersectionPoint,  vA, vB, vC, uvA, uvB, uvC );

				}

				intersection.face = new Face3( a, b, c, Triangle.normal( vA, vB, vC ) );
				intersection.faceIndex = a;

			}

			return intersection;

		}

		return function raycast( raycaster, intersects ) {

			var geometry = this.geometry;
			var material = this.material;
			var matrixWorld = this.matrixWorld;

			if ( material === undefined ) return;

			// Checking boundingSphere distance to ray

			if ( geometry.boundingSphere === null ) geometry.computeBoundingSphere();

			sphere.copy( geometry.boundingSphere );
			sphere.applyMatrix4( matrixWorld );

			if ( raycaster.ray.intersectsSphere( sphere ) === false ) return;

			//

			inverseMatrix.getInverse( matrixWorld );
			ray.copy( raycaster.ray ).applyMatrix4( inverseMatrix );

			// Check boundingBox before continuing

			if ( geometry.boundingBox !== null ) {

				if ( ray.intersectsBox( geometry.boundingBox ) === false ) return;

			}

			var intersection;

			if ( geometry.isBufferGeometry ) {

				var a, b, c;
				var index = geometry.index;
				var position = geometry.attributes.position;
				var uv = geometry.attributes.uv;
				var i, l;

				if ( index !== null ) {

					// indexed buffer geometry

					for ( i = 0, l = index.count; i < l; i += 3 ) {

						a = index.getX( i );
						b = index.getX( i + 1 );
						c = index.getX( i + 2 );

						intersection = checkBufferGeometryIntersection( this, raycaster, ray, position, uv, a, b, c );

						if ( intersection ) {

							intersection.faceIndex = Math.floor( i / 3 ); // triangle number in indices buffer semantics
							intersects.push( intersection );

						}

					}

				} else {

					// non-indexed buffer geometry

					for ( i = 0, l = position.count; i < l; i += 3 ) {

						a = i;
						b = i + 1;
						c = i + 2;

						intersection = checkBufferGeometryIntersection( this, raycaster, ray, position, uv, a, b, c );

						if ( intersection ) {

							intersection.index = a; // triangle number in positions buffer semantics
							intersects.push( intersection );

						}

					}

				}

			} else if ( geometry.isGeometry ) {

				var fvA, fvB, fvC;
				var isFaceMaterial = ( material && material.isMultiMaterial );
				var materials = isFaceMaterial === true ? material.materials : null;

				var vertices = geometry.vertices;
				var faces = geometry.faces;
				var uvs;

				var faceVertexUvs = geometry.faceVertexUvs[ 0 ];
				if ( faceVertexUvs.length > 0 ) uvs = faceVertexUvs;

				for ( var f = 0, fl = faces.length; f < fl; f ++ ) {

					var face = faces[ f ];
					var faceMaterial = isFaceMaterial === true ? materials[ face.materialIndex ] : material;

					if ( faceMaterial === undefined ) continue;

					fvA = vertices[ face.a ];
					fvB = vertices[ face.b ];
					fvC = vertices[ face.c ];

					if ( faceMaterial.morphTargets === true ) {

						var morphTargets = geometry.morphTargets;
						var morphInfluences = this.morphTargetInfluences;

						vA.set( 0, 0, 0 );
						vB.set( 0, 0, 0 );
						vC.set( 0, 0, 0 );

						for ( var t = 0, tl = morphTargets.length; t < tl; t ++ ) {

							var influence = morphInfluences[ t ];

							if ( influence === 0 ) continue;

							var targets = morphTargets[ t ].vertices;

							vA.addScaledVector( tempA.subVectors( targets[ face.a ], fvA ), influence );
							vB.addScaledVector( tempB.subVectors( targets[ face.b ], fvB ), influence );
							vC.addScaledVector( tempC.subVectors( targets[ face.c ], fvC ), influence );

						}

						vA.add( fvA );
						vB.add( fvB );
						vC.add( fvC );

						fvA = vA;
						fvB = vB;
						fvC = vC;

					}

					intersection = checkIntersection( this, raycaster, ray, fvA, fvB, fvC, intersectionPoint );

					if ( intersection ) {

						if ( uvs ) {

							var uvs_f = uvs[ f ];
							uvA.copy( uvs_f[ 0 ] );
							uvB.copy( uvs_f[ 1 ] );
							uvC.copy( uvs_f[ 2 ] );

							intersection.uv = uvIntersection( intersectionPoint, fvA, fvB, fvC, uvA, uvB, uvC );

						}

						intersection.face = face;
						intersection.faceIndex = f;
						intersects.push( intersection );

					}

				}

			}

		};

	}() ),

	clone: function () {

		return new this.constructor( this.geometry, this.material ).copy( this );

	}

} );

/**
 * @author mrdoob / http://mrdoob.com/
 * based on http://papervision3d.googlecode.com/svn/trunk/as3/trunk/src/org/papervision3d/objects/primitives/Cube.as
 */

/**
 * @author Mugen87 / https://github.com/Mugen87
 */

function BoxBufferGeometry( width, height, depth, widthSegments, heightSegments, depthSegments ) {

	BufferGeometry.call( this );

	this.type = 'BoxBufferGeometry';

	this.parameters = {
		width: width,
		height: height,
		depth: depth,
		widthSegments: widthSegments,
		heightSegments: heightSegments,
		depthSegments: depthSegments
	};

	var scope = this;

	// segments

	widthSegments = Math.floor( widthSegments ) || 1;
	heightSegments = Math.floor( heightSegments ) || 1;
	depthSegments = Math.floor( depthSegments ) || 1;

	// buffers

	var indices = [];
	var vertices = [];
	var normals = [];
	var uvs = [];

	// helper variables

	var numberOfVertices = 0;
	var groupStart = 0;

	// build each side of the box geometry

	buildPlane( 'z', 'y', 'x', - 1, - 1, depth, height,   width,  depthSegments, heightSegments, 0 ); // px
	buildPlane( 'z', 'y', 'x',   1, - 1, depth, height, - width,  depthSegments, heightSegments, 1 ); // nx
	buildPlane( 'x', 'z', 'y',   1,   1, width, depth,    height, widthSegments, depthSegments,  2 ); // py
	buildPlane( 'x', 'z', 'y',   1, - 1, width, depth,  - height, widthSegments, depthSegments,  3 ); // ny
	buildPlane( 'x', 'y', 'z',   1, - 1, width, height,   depth,  widthSegments, heightSegments, 4 ); // pz
	buildPlane( 'x', 'y', 'z', - 1, - 1, width, height, - depth,  widthSegments, heightSegments, 5 ); // nz

	// build geometry

	this.setIndex( indices );
	this.addAttribute( 'position', new Float32BufferAttribute( vertices, 3 ) );
	this.addAttribute( 'normal', new Float32BufferAttribute( normals, 3 ) );
	this.addAttribute( 'uv', new Float32BufferAttribute( uvs, 2 ) );

	function buildPlane( u, v, w, udir, vdir, width, height, depth, gridX, gridY, materialIndex ) {

		var segmentWidth = width / gridX;
		var segmentHeight = height / gridY;

		var widthHalf = width / 2;
		var heightHalf = height / 2;
		var depthHalf = depth / 2;

		var gridX1 = gridX + 1;
		var gridY1 = gridY + 1;

		var vertexCounter = 0;
		var groupCount = 0;

		var ix, iy;

		var vector = new Vector3();

		// generate vertices, normals and uvs

		for ( iy = 0; iy < gridY1; iy ++ ) {

			var y = iy * segmentHeight - heightHalf;

			for ( ix = 0; ix < gridX1; ix ++ ) {

				var x = ix * segmentWidth - widthHalf;

				// set values to correct vector component

				vector[ u ] = x * udir;
				vector[ v ] = y * vdir;
				vector[ w ] = depthHalf;

				// now apply vector to vertex buffer

				vertices.push( vector.x, vector.y, vector.z );

				// set values to correct vector component

				vector[ u ] = 0;
				vector[ v ] = 0;
				vector[ w ] = depth > 0 ? 1 : - 1;

				// now apply vector to normal buffer

				normals.push( vector.x, vector.y, vector.z );

				// uvs

				uvs.push( ix / gridX );
				uvs.push( 1 - ( iy / gridY ) );

				// counters

				vertexCounter += 1;

			}

		}

		// indices

		// 1. you need three indices to draw a single face
		// 2. a single segment consists of two faces
		// 3. so we need to generate six (2*3) indices per segment

		for ( iy = 0; iy < gridY; iy ++ ) {

			for ( ix = 0; ix < gridX; ix ++ ) {

				var a = numberOfVertices + ix + gridX1 * iy;
				var b = numberOfVertices + ix + gridX1 * ( iy + 1 );
				var c = numberOfVertices + ( ix + 1 ) + gridX1 * ( iy + 1 );
				var d = numberOfVertices + ( ix + 1 ) + gridX1 * iy;

				// faces

				indices.push( a, b, d );
				indices.push( b, c, d );

				// increase counter

				groupCount += 6;

			}

		}

		// add a group to the geometry. this will ensure multi material support

		scope.addGroup( groupStart, groupCount, materialIndex );

		// calculate new start value for groups

		groupStart += groupCount;

		// update total number of vertices

		numberOfVertices += vertexCounter;

	}

}

BoxBufferGeometry.prototype = Object.create( BufferGeometry.prototype );
BoxBufferGeometry.prototype.constructor = BoxBufferGeometry;

/**
 * @author mrdoob / http://mrdoob.com/
 * based on http://papervision3d.googlecode.com/svn/trunk/as3/trunk/src/org/papervision3d/objects/primitives/Plane.as
 */

/**
 * @author mrdoob / http://mrdoob.com/
 * @author Mugen87 / https://github.com/Mugen87
 *
 * based on http://papervision3d.googlecode.com/svn/trunk/as3/trunk/src/org/papervision3d/objects/primitives/Plane.as
 */

function PlaneBufferGeometry( width, height, widthSegments, heightSegments ) {

	BufferGeometry.call( this );

	this.type = 'PlaneBufferGeometry';

	this.parameters = {
		width: width,
		height: height,
		widthSegments: widthSegments,
		heightSegments: heightSegments
	};

	var width_half = width / 2;
	var height_half = height / 2;

	var gridX = Math.floor( widthSegments ) || 1;
	var gridY = Math.floor( heightSegments ) || 1;

	var gridX1 = gridX + 1;
	var gridY1 = gridY + 1;

	var segment_width = width / gridX;
	var segment_height = height / gridY;

	var ix, iy;

	// buffers

	var indices = [];
	var vertices = [];
	var normals = [];
	var uvs = [];

	// generate vertices, normals and uvs

	for ( iy = 0; iy < gridY1; iy ++ ) {

		var y = iy * segment_height - height_half;

		for ( ix = 0; ix < gridX1; ix ++ ) {

			var x = ix * segment_width - width_half;

			vertices.push( x, - y, 0 );

			normals.push( 0, 0, 1 );

			uvs.push( ix / gridX );
			uvs.push( 1 - ( iy / gridY ) );

		}

	}

	// indices

	for ( iy = 0; iy < gridY; iy ++ ) {

		for ( ix = 0; ix < gridX; ix ++ ) {

			var a = ix + gridX1 * iy;
			var b = ix + gridX1 * ( iy + 1 );
			var c = ( ix + 1 ) + gridX1 * ( iy + 1 );
			var d = ( ix + 1 ) + gridX1 * iy;

			// faces

			indices.push( a, b, d );
			indices.push( b, c, d );

		}

	}

	// build geometry

	this.setIndex( indices );
	this.addAttribute( 'position', new Float32BufferAttribute( vertices, 3 ) );
	this.addAttribute( 'normal', new Float32BufferAttribute( normals, 3 ) );
	this.addAttribute( 'uv', new Float32BufferAttribute( uvs, 2 ) );

}

PlaneBufferGeometry.prototype = Object.create( BufferGeometry.prototype );
PlaneBufferGeometry.prototype.constructor = PlaneBufferGeometry;

/**
 * @author mrdoob / http://mrdoob.com/
 * @author mikael emtinger / http://gomo.se/
 * @author WestLangley / http://github.com/WestLangley
*/

function Camera() {

	Object3D.call( this );

	this.type = 'Camera';

	this.matrixWorldInverse = new Matrix4();
	this.projectionMatrix = new Matrix4();

}

Camera.prototype = Object.create( Object3D.prototype );
Camera.prototype.constructor = Camera;

Camera.prototype.isCamera = true;

Camera.prototype.getWorldDirection = function () {

	var quaternion = new Quaternion();

	return function getWorldDirection( optionalTarget ) {

		var result = optionalTarget || new Vector3();

		this.getWorldQuaternion( quaternion );

		return result.set( 0, 0, - 1 ).applyQuaternion( quaternion );

	};

}();

Camera.prototype.lookAt = function () {

	// This routine does not support cameras with rotated and/or translated parent(s)

	var m1 = new Matrix4();

	return function lookAt( vector ) {

		m1.lookAt( this.position, vector, this.up );

		this.quaternion.setFromRotationMatrix( m1 );

	};

}();

Camera.prototype.clone = function () {

	return new this.constructor().copy( this );

};

Camera.prototype.copy = function ( source ) {

	Object3D.prototype.copy.call( this, source );

	this.matrixWorldInverse.copy( source.matrixWorldInverse );
	this.projectionMatrix.copy( source.projectionMatrix );

	return this;

};

/**
 * @author mrdoob / http://mrdoob.com/
 * @author greggman / http://games.greggman.com/
 * @author zz85 / http://www.lab4games.net/zz85/blog
 * @author tschw
 */

function PerspectiveCamera( fov, aspect, near, far ) {

	Camera.call( this );

	this.type = 'PerspectiveCamera';

	this.fov = fov !== undefined ? fov : 50;
	this.zoom = 1;

	this.near = near !== undefined ? near : 0.1;
	this.far = far !== undefined ? far : 2000;
	this.focus = 10;

	this.aspect = aspect !== undefined ? aspect : 1;
	this.view = null;

	this.filmGauge = 35;	// width of the film (default in millimeters)
	this.filmOffset = 0;	// horizontal film offset (same unit as gauge)

	this.updateProjectionMatrix();

}

PerspectiveCamera.prototype = Object.assign( Object.create( Camera.prototype ), {

	constructor: PerspectiveCamera,

	isPerspectiveCamera: true,

	copy: function ( source ) {

		Camera.prototype.copy.call( this, source );

		this.fov = source.fov;
		this.zoom = source.zoom;

		this.near = source.near;
		this.far = source.far;
		this.focus = source.focus;

		this.aspect = source.aspect;
		this.view = source.view === null ? null : Object.assign( {}, source.view );

		this.filmGauge = source.filmGauge;
		this.filmOffset = source.filmOffset;

		return this;

	},

	/**
	 * Sets the FOV by focal length in respect to the current .filmGauge.
	 *
	 * The default film gauge is 35, so that the focal length can be specified for
	 * a 35mm (full frame) camera.
	 *
	 * Values for focal length and film gauge must have the same unit.
	 */
	setFocalLength: function ( focalLength ) {

		// see http://www.bobatkins.com/photography/technical/field_of_view.html
		var vExtentSlope = 0.5 * this.getFilmHeight() / focalLength;

		this.fov = _Math.RAD2DEG * 2 * Math.atan( vExtentSlope );
		this.updateProjectionMatrix();

	},

	/**
	 * Calculates the focal length from the current .fov and .filmGauge.
	 */
	getFocalLength: function () {

		var vExtentSlope = Math.tan( _Math.DEG2RAD * 0.5 * this.fov );

		return 0.5 * this.getFilmHeight() / vExtentSlope;

	},

	getEffectiveFOV: function () {

		return _Math.RAD2DEG * 2 * Math.atan(
				Math.tan( _Math.DEG2RAD * 0.5 * this.fov ) / this.zoom );

	},

	getFilmWidth: function () {

		// film not completely covered in portrait format (aspect < 1)
		return this.filmGauge * Math.min( this.aspect, 1 );

	},

	getFilmHeight: function () {

		// film not completely covered in landscape format (aspect > 1)
		return this.filmGauge / Math.max( this.aspect, 1 );

	},

	/**
	 * Sets an offset in a larger frustum. This is useful for multi-window or
	 * multi-monitor/multi-machine setups.
	 *
	 * For example, if you have 3x2 monitors and each monitor is 1920x1080 and
	 * the monitors are in grid like this
	 *
	 *   +---+---+---+
	 *   | A | B | C |
	 *   +---+---+---+
	 *   | D | E | F |
	 *   +---+---+---+
	 *
	 * then for each monitor you would call it like this
	 *
	 *   var w = 1920;
	 *   var h = 1080;
	 *   var fullWidth = w * 3;
	 *   var fullHeight = h * 2;
	 *
	 *   --A--
	 *   camera.setOffset( fullWidth, fullHeight, w * 0, h * 0, w, h );
	 *   --B--
	 *   camera.setOffset( fullWidth, fullHeight, w * 1, h * 0, w, h );
	 *   --C--
	 *   camera.setOffset( fullWidth, fullHeight, w * 2, h * 0, w, h );
	 *   --D--
	 *   camera.setOffset( fullWidth, fullHeight, w * 0, h * 1, w, h );
	 *   --E--
	 *   camera.setOffset( fullWidth, fullHeight, w * 1, h * 1, w, h );
	 *   --F--
	 *   camera.setOffset( fullWidth, fullHeight, w * 2, h * 1, w, h );
	 *
	 *   Note there is no reason monitors have to be the same size or in a grid.
	 */
	setViewOffset: function ( fullWidth, fullHeight, x, y, width, height ) {

		this.aspect = fullWidth / fullHeight;

		this.view = {
			fullWidth: fullWidth,
			fullHeight: fullHeight,
			offsetX: x,
			offsetY: y,
			width: width,
			height: height
		};

		this.updateProjectionMatrix();

	},

	clearViewOffset: function() {

		this.view = null;
		this.updateProjectionMatrix();

	},

	updateProjectionMatrix: function () {

		var near = this.near,
			top = near * Math.tan(
					_Math.DEG2RAD * 0.5 * this.fov ) / this.zoom,
			height = 2 * top,
			width = this.aspect * height,
			left = - 0.5 * width,
			view = this.view;

		if ( view !== null ) {

			var fullWidth = view.fullWidth,
				fullHeight = view.fullHeight;

			left += view.offsetX * width / fullWidth;
			top -= view.offsetY * height / fullHeight;
			width *= view.width / fullWidth;
			height *= view.height / fullHeight;

		}

		var skew = this.filmOffset;
		if ( skew !== 0 ) left += near * skew / this.getFilmWidth();

		this.projectionMatrix.makePerspective( left, left + width, top, top - height, near, this.far );

	},

	toJSON: function ( meta ) {

		var data = Object3D.prototype.toJSON.call( this, meta );

		data.object.fov = this.fov;
		data.object.zoom = this.zoom;

		data.object.near = this.near;
		data.object.far = this.far;
		data.object.focus = this.focus;

		data.object.aspect = this.aspect;

		if ( this.view !== null ) data.object.view = Object.assign( {}, this.view );

		data.object.filmGauge = this.filmGauge;
		data.object.filmOffset = this.filmOffset;

		return data;

	}

} );

/**
 * @author alteredq / http://alteredqualia.com/
 * @author arose / http://github.com/arose
 */

function OrthographicCamera( left, right, top, bottom, near, far ) {

	Camera.call( this );

	this.type = 'OrthographicCamera';

	this.zoom = 1;
	this.view = null;

	this.left = left;
	this.right = right;
	this.top = top;
	this.bottom = bottom;

	this.near = ( near !== undefined ) ? near : 0.1;
	this.far = ( far !== undefined ) ? far : 2000;

	this.updateProjectionMatrix();

}

OrthographicCamera.prototype = Object.assign( Object.create( Camera.prototype ), {

	constructor: OrthographicCamera,

	isOrthographicCamera: true,

	copy: function ( source ) {

		Camera.prototype.copy.call( this, source );

		this.left = source.left;
		this.right = source.right;
		this.top = source.top;
		this.bottom = source.bottom;
		this.near = source.near;
		this.far = source.far;

		this.zoom = source.zoom;
		this.view = source.view === null ? null : Object.assign( {}, source.view );

		return this;

	},

	setViewOffset: function( fullWidth, fullHeight, x, y, width, height ) {

		this.view = {
			fullWidth: fullWidth,
			fullHeight: fullHeight,
			offsetX: x,
			offsetY: y,
			width: width,
			height: height
		};

		this.updateProjectionMatrix();

	},

	clearViewOffset: function() {

		this.view = null;
		this.updateProjectionMatrix();

	},

	updateProjectionMatrix: function () {

		var dx = ( this.right - this.left ) / ( 2 * this.zoom );
		var dy = ( this.top - this.bottom ) / ( 2 * this.zoom );
		var cx = ( this.right + this.left ) / 2;
		var cy = ( this.top + this.bottom ) / 2;

		var left = cx - dx;
		var right = cx + dx;
		var top = cy + dy;
		var bottom = cy - dy;

		if ( this.view !== null ) {

			var zoomW = this.zoom / ( this.view.width / this.view.fullWidth );
			var zoomH = this.zoom / ( this.view.height / this.view.fullHeight );
			var scaleW = ( this.right - this.left ) / this.view.width;
			var scaleH = ( this.top - this.bottom ) / this.view.height;

			left += scaleW * ( this.view.offsetX / zoomW );
			right = left + scaleW * ( this.view.width / zoomW );
			top -= scaleH * ( this.view.offsetY / zoomH );
			bottom = top - scaleH * ( this.view.height / zoomH );

		}

		this.projectionMatrix.makeOrthographic( left, right, top, bottom, this.near, this.far );

	},

	toJSON: function ( meta ) {

		var data = Object3D.prototype.toJSON.call( this, meta );

		data.object.zoom = this.zoom;
		data.object.left = this.left;
		data.object.right = this.right;
		data.object.top = this.top;
		data.object.bottom = this.bottom;
		data.object.near = this.near;
		data.object.far = this.far;

		if ( this.view !== null ) data.object.view = Object.assign( {}, this.view );

		return data;

	}

} );

/**
 * @author mrdoob / http://mrdoob.com/
 */

function WebGLIndexedBufferRenderer( gl, extensions, infoRender ) {

	var mode;

	function setMode( value ) {

		mode = value;

	}

	var type, size;

	function setIndex( index ) {

		if ( index.array instanceof Uint32Array && extensions.get( 'OES_element_index_uint' ) ) {

			type = gl.UNSIGNED_INT;
			size = 4;

		} else if ( index.array instanceof Uint16Array ) {

			type = gl.UNSIGNED_SHORT;
			size = 2;

		} else {

			type = gl.UNSIGNED_BYTE;
			size = 1;

		}

	}

	function render( start, count ) {

		gl.drawElements( mode, count, type, start * size );

		infoRender.calls ++;
		infoRender.vertices += count;

		if ( mode === gl.TRIANGLES ) infoRender.faces += count / 3;

	}

	function renderInstances( geometry, start, count ) {

		var extension = extensions.get( 'ANGLE_instanced_arrays' );

		if ( extension === null ) {

			console.error( 'THREE.WebGLBufferRenderer: using THREE.InstancedBufferGeometry but hardware does not support extension ANGLE_instanced_arrays.' );
			return;

		}

		extension.drawElementsInstancedANGLE( mode, count, type, start * size, geometry.maxInstancedCount );

		infoRender.calls ++;
		infoRender.vertices += count * geometry.maxInstancedCount;

		if ( mode === gl.TRIANGLES ) infoRender.faces += geometry.maxInstancedCount * count / 3;

	}

	return {

		setMode: setMode,
		setIndex: setIndex,
		render: render,
		renderInstances: renderInstances

	};

}

/**
 * @author mrdoob / http://mrdoob.com/
 */

function WebGLBufferRenderer( gl, extensions, infoRender ) {

	var mode;

	function setMode( value ) {

		mode = value;

	}

	function render( start, count ) {

		gl.drawArrays( mode, start, count );

		infoRender.calls ++;
		infoRender.vertices += count;

		if ( mode === gl.TRIANGLES ) infoRender.faces += count / 3;

	}

	function renderInstances( geometry ) {

		var extension = extensions.get( 'ANGLE_instanced_arrays' );

		if ( extension === null ) {

			console.error( 'THREE.WebGLBufferRenderer: using THREE.InstancedBufferGeometry but hardware does not support extension ANGLE_instanced_arrays.' );
			return;

		}

		var position = geometry.attributes.position;

		var count = 0;

		if ( position.isInterleavedBufferAttribute ) {

			count = position.data.count;

			extension.drawArraysInstancedANGLE( mode, 0, count, geometry.maxInstancedCount );

		} else {

			count = position.count;

			extension.drawArraysInstancedANGLE( mode, 0, count, geometry.maxInstancedCount );

		}

		infoRender.calls ++;
		infoRender.vertices += count * geometry.maxInstancedCount;

		if ( mode === gl.TRIANGLES ) infoRender.faces += geometry.maxInstancedCount * count / 3;

	}

	return {
		setMode: setMode,
		render: render,
		renderInstances: renderInstances
	};

}

/**
 * @author mrdoob / http://mrdoob.com/
 */

function WebGLLights() {

	var lights = {};

	return {

		get: function ( light ) {

			if ( lights[ light.id ] !== undefined ) {

				return lights[ light.id ];

			}

			var uniforms;

			switch ( light.type ) {

				case 'DirectionalLight':
					uniforms = {
						direction: new Vector3(),
						color: new Color(),

						shadow: false,
						shadowBias: 0,
						shadowRadius: 1,
						shadowMapSize: new Vector2()
					};
					break;

				case 'SpotLight':
					uniforms = {
						position: new Vector3(),
						direction: new Vector3(),
						color: new Color(),
						distance: 0,
						coneCos: 0,
						penumbraCos: 0,
						decay: 0,

						shadow: false,
						shadowBias: 0,
						shadowRadius: 1,
						shadowMapSize: new Vector2()
					};
					break;

				case 'PointLight':
					uniforms = {
						position: new Vector3(),
						color: new Color(),
						distance: 0,
						decay: 0,

						shadow: false,
						shadowBias: 0,
						shadowRadius: 1,
						shadowMapSize: new Vector2()
					};
					break;

				case 'HemisphereLight':
					uniforms = {
						direction: new Vector3(),
						skyColor: new Color(),
						groundColor: new Color()
					};
					break;

				case 'RectAreaLight':
					uniforms = {
						color: new Color(),
						position: new Vector3(),
						halfWidth: new Vector3(),
						halfHeight: new Vector3()
						// TODO (abelnation): set RectAreaLight shadow uniforms
					};
					break;

			}

			lights[ light.id ] = uniforms;

			return uniforms;

		}

	};

}

/**
 * @author mrdoob / http://mrdoob.com/
 */

function addLineNumbers( string ) {

	var lines = string.split( '\n' );

	for ( var i = 0; i < lines.length; i ++ ) {

		lines[ i ] = ( i + 1 ) + ': ' + lines[ i ];

	}

	return lines.join( '\n' );

}

function WebGLShader( gl, type, string ) {

	var shader = gl.createShader( type );

	gl.shaderSource( shader, string );
	gl.compileShader( shader );

	if ( gl.getShaderParameter( shader, gl.COMPILE_STATUS ) === false ) {

		console.error( 'THREE.WebGLShader: Shader couldn\'t compile.' );

	}

	if ( gl.getShaderInfoLog( shader ) !== '' ) {

		console.warn( 'THREE.WebGLShader: gl.getShaderInfoLog()', type === gl.VERTEX_SHADER ? 'vertex' : 'fragment', gl.getShaderInfoLog( shader ), addLineNumbers( string ) );

	}

	// --enable-privileged-webgl-extension
	// console.log( type, gl.getExtension( 'WEBGL_debug_shaders' ).getTranslatedShaderSource( shader ) );

	return shader;

}

/**
 * @author mrdoob / http://mrdoob.com/
 */

var programIdCount = 0;

function getEncodingComponents( encoding ) {

	switch ( encoding ) {

		case LinearEncoding:
			return [ 'Linear','( value )' ];
		case sRGBEncoding:
			return [ 'sRGB','( value )' ];
		case RGBEEncoding:
			return [ 'RGBE','( value )' ];
		case RGBM7Encoding:
			return [ 'RGBM','( value, 7.0 )' ];
		case RGBM16Encoding:
			return [ 'RGBM','( value, 16.0 )' ];
		case RGBDEncoding:
			return [ 'RGBD','( value, 256.0 )' ];
		case GammaEncoding:
			return [ 'Gamma','( value, float( GAMMA_FACTOR ) )' ];
		default:
			throw new Error( 'unsupported encoding: ' + encoding );

	}

}

function getTexelDecodingFunction( functionName, encoding ) {

	var components = getEncodingComponents( encoding );
	return "vec4 " + functionName + "( vec4 value ) { return " + components[ 0 ] + "ToLinear" + components[ 1 ] + "; }";

}

function getTexelEncodingFunction( functionName, encoding ) {

	var components = getEncodingComponents( encoding );
	return "vec4 " + functionName + "( vec4 value ) { return LinearTo" + components[ 0 ] + components[ 1 ] + "; }";

}

function getToneMappingFunction( functionName, toneMapping ) {

	var toneMappingName;

	switch ( toneMapping ) {

		case LinearToneMapping:
			toneMappingName = "Linear";
			break;

		case ReinhardToneMapping:
			toneMappingName = "Reinhard";
			break;

		case Uncharted2ToneMapping:
			toneMappingName = "Uncharted2";
			break;

		case CineonToneMapping:
			toneMappingName = "OptimizedCineon";
			break;

		default:
			throw new Error( 'unsupported toneMapping: ' + toneMapping );

	}

	return "vec3 " + functionName + "( vec3 color ) { return " + toneMappingName + "ToneMapping( color ); }";

}

function generateExtensions( extensions, parameters, rendererExtensions ) {

	extensions = extensions || {};

	var chunks = [
		( extensions.derivatives || parameters.envMapCubeUV || parameters.bumpMap || parameters.normalMap || parameters.flatShading ) ? '#extension GL_OES_standard_derivatives : enable' : '',
		( extensions.fragDepth || parameters.logarithmicDepthBuffer ) && rendererExtensions.get( 'EXT_frag_depth' ) ? '#extension GL_EXT_frag_depth : enable' : '',
		( extensions.drawBuffers ) && rendererExtensions.get( 'WEBGL_draw_buffers' ) ? '#extension GL_EXT_draw_buffers : require' : '',
		( extensions.shaderTextureLOD || parameters.envMap ) && rendererExtensions.get( 'EXT_shader_texture_lod' ) ? '#extension GL_EXT_shader_texture_lod : enable' : ''
	];

	return chunks.filter( filterEmptyLine ).join( '\n' );

}

function generateDefines( defines ) {

	var chunks = [];

	for ( var name in defines ) {

		var value = defines[ name ];

		if ( value === false ) continue;

		chunks.push( '#define ' + name + ' ' + value );

	}

	return chunks.join( '\n' );

}

function fetchAttributeLocations( gl, program, identifiers ) {

	var attributes = {};

	var n = gl.getProgramParameter( program, gl.ACTIVE_ATTRIBUTES );

	for ( var i = 0; i < n; i ++ ) {

		var info = gl.getActiveAttrib( program, i );
		var name = info.name;

		// console.log("THREE.WebGLProgram: ACTIVE VERTEX ATTRIBUTE:", name, i );

		attributes[ name ] = gl.getAttribLocation( program, name );

	}

	return attributes;

}

function filterEmptyLine( string ) {

	return string !== '';

}

function replaceLightNums( string, parameters ) {

	return string
		.replace( /NUM_DIR_LIGHTS/g, parameters.numDirLights )
		.replace( /NUM_SPOT_LIGHTS/g, parameters.numSpotLights )
		.replace( /NUM_RECT_AREA_LIGHTS/g, parameters.numRectAreaLights )
		.replace( /NUM_POINT_LIGHTS/g, parameters.numPointLights )
		.replace( /NUM_HEMI_LIGHTS/g, parameters.numHemiLights );

}

function parseIncludes( string ) {

	var pattern = /#include +<([\w\d.]+)>/g;

	function replace( match, include ) {

		var replace = ShaderChunk[ include ];

		if ( replace === undefined ) {

			throw new Error( 'Can not resolve #include <' + include + '>' );

		}

		return parseIncludes( replace );

	}

	return string.replace( pattern, replace );

}

function unrollLoops( string ) {

	var pattern = /for \( int i \= (\d+)\; i < (\d+)\; i \+\+ \) \{([\s\S]+?)(?=\})\}/g;

	function replace( match, start, end, snippet ) {

		var unroll = '';

		for ( var i = parseInt( start ); i < parseInt( end ); i ++ ) {

			unroll += snippet.replace( /\[ i \]/g, '[ ' + i + ' ]' );

		}

		return unroll;

	}

	return string.replace( pattern, replace );

}

function WebGLProgram( renderer, code, material, parameters ) {

	var gl = renderer.context;

	var extensions = material.extensions;
	var defines = material.defines;

	var vertexShader = material.__webglShader.vertexShader;
	var fragmentShader = material.__webglShader.fragmentShader;

	var shadowMapTypeDefine = 'SHADOWMAP_TYPE_BASIC';

	if ( parameters.shadowMapType === PCFShadowMap ) {

		shadowMapTypeDefine = 'SHADOWMAP_TYPE_PCF';

	} else if ( parameters.shadowMapType === PCFSoftShadowMap ) {

		shadowMapTypeDefine = 'SHADOWMAP_TYPE_PCF_SOFT';

	}

	var envMapTypeDefine = 'ENVMAP_TYPE_CUBE';
	var envMapModeDefine = 'ENVMAP_MODE_REFLECTION';
	var envMapBlendingDefine = 'ENVMAP_BLENDING_MULTIPLY';

	if ( parameters.envMap ) {

		switch ( material.envMap.mapping ) {

			case CubeReflectionMapping:
			case CubeRefractionMapping:
				envMapTypeDefine = 'ENVMAP_TYPE_CUBE';
				break;

			case CubeUVReflectionMapping:
			case CubeUVRefractionMapping:
				envMapTypeDefine = 'ENVMAP_TYPE_CUBE_UV';
				break;

			case EquirectangularReflectionMapping:
			case EquirectangularRefractionMapping:
				envMapTypeDefine = 'ENVMAP_TYPE_EQUIREC';
				break;

			case SphericalReflectionMapping:
				envMapTypeDefine = 'ENVMAP_TYPE_SPHERE';
				break;

		}

		switch ( material.envMap.mapping ) {

			case CubeRefractionMapping:
			case EquirectangularRefractionMapping:
				envMapModeDefine = 'ENVMAP_MODE_REFRACTION';
				break;

		}

		switch ( material.combine ) {

			case MultiplyOperation:
				envMapBlendingDefine = 'ENVMAP_BLENDING_MULTIPLY';
				break;

			case MixOperation:
				envMapBlendingDefine = 'ENVMAP_BLENDING_MIX';
				break;

			case AddOperation:
				envMapBlendingDefine = 'ENVMAP_BLENDING_ADD';
				break;

		}

	}

	var gammaFactorDefine = ( renderer.gammaFactor > 0 ) ? renderer.gammaFactor : 1.0;

	// console.log( 'building new program ' );

	//

	var customExtensions = generateExtensions( extensions, parameters, renderer.extensions );

	var customDefines = generateDefines( defines );

	//

	var program = gl.createProgram();

	var prefixVertex, prefixFragment;

	if ( material.isRawShaderMaterial ) {

		prefixVertex = [

			customDefines,

			'\n'

		].filter( filterEmptyLine ).join( '\n' );

		prefixFragment = [

			customExtensions,
			customDefines,

			'\n'

		].filter( filterEmptyLine ).join( '\n' );

	} else {

		prefixVertex = [

        
			'precision ' + parameters.precision + ' float;',
			'precision ' + parameters.precision + ' int;',

			'#define SHADER_NAME ' + material.__webglShader.name,

			customDefines,

			parameters.supportsVertexTextures ? '#define VERTEX_TEXTURES' : '',

			'#define GAMMA_FACTOR ' + gammaFactorDefine,

			'#define MAX_BONES ' + parameters.maxBones,
			( parameters.useFog && parameters.fog ) ? '#define USE_FOG' : '',
			( parameters.useFog && parameters.fogExp ) ? '#define FOG_EXP2' : '',


			parameters.map ? '#define USE_MAP' : '',
			parameters.envMap ? '#define USE_ENVMAP' : '',
			parameters.envMap ? '#define ' + envMapModeDefine : '',
			parameters.lightMap ? '#define USE_LIGHTMAP' : '',
			parameters.aoMap ? '#define USE_AOMAP' : '',
			parameters.emissiveMap ? '#define USE_EMISSIVEMAP' : '',
			parameters.bumpMap ? '#define USE_BUMPMAP' : '',
			parameters.normalMap ? '#define USE_NORMALMAP' : '',
			parameters.displacementMap && parameters.supportsVertexTextures ? '#define USE_DISPLACEMENTMAP' : '',
			parameters.specularMap ? '#define USE_SPECULARMAP' : '',
			parameters.roughnessMap ? '#define USE_ROUGHNESSMAP' : '',
			parameters.metalnessMap ? '#define USE_METALNESSMAP' : '',
			parameters.alphaMap ? '#define USE_ALPHAMAP' : '',
			parameters.vertexColors ? '#define USE_COLOR' : '',

			parameters.flatShading ? '#define FLAT_SHADED' : '',

			parameters.skinning ? '#define USE_SKINNING' : '',
			parameters.useVertexTexture ? '#define BONE_TEXTURE' : '',

			parameters.morphTargets ? '#define USE_MORPHTARGETS' : '',
			parameters.morphNormals && parameters.flatShading === false ? '#define USE_MORPHNORMALS' : '',
			parameters.doubleSided ? '#define DOUBLE_SIDED' : '',
			parameters.flipSided ? '#define FLIP_SIDED' : '',

			'#define NUM_CLIPPING_PLANES ' + parameters.numClippingPlanes,

			parameters.shadowMapEnabled ? '#define USE_SHADOWMAP' : '',
			parameters.shadowMapEnabled ? '#define ' + shadowMapTypeDefine : '',

			parameters.sizeAttenuation ? '#define USE_SIZEATTENUATION' : '',

			parameters.logarithmicDepthBuffer ? '#define USE_LOGDEPTHBUF' : '',
			parameters.logarithmicDepthBuffer && renderer.extensions.get( 'EXT_frag_depth' ) ? '#define USE_LOGDEPTHBUF_EXT' : '',

			'uniform mat4 modelMatrix;',
			'uniform mat4 modelViewMatrix;',
			'uniform mat4 projectionMatrix;',
			'uniform mat4 viewMatrix;',
			'uniform mat3 normalMatrix;',
			'uniform vec3 cameraPosition;',

			'attribute vec3 position;',
			'attribute vec3 normal;',
			'attribute vec2 uv;',

			'#ifdef USE_COLOR',

			'	attribute vec3 color;',

			'#endif',

			'#ifdef USE_MORPHTARGETS',

			'	attribute vec3 morphTarget0;',
			'	attribute vec3 morphTarget1;',
			'	attribute vec3 morphTarget2;',
			'	attribute vec3 morphTarget3;',

			'	#ifdef USE_MORPHNORMALS',

			'		attribute vec3 morphNormal0;',
			'		attribute vec3 morphNormal1;',
			'		attribute vec3 morphNormal2;',
			'		attribute vec3 morphNormal3;',

			'	#else',

			'		attribute vec3 morphTarget4;',
			'		attribute vec3 morphTarget5;',
			'		attribute vec3 morphTarget6;',
			'		attribute vec3 morphTarget7;',

			'	#endif',

			'#endif',

			'#ifdef USE_SKINNING',

			'	attribute vec4 skinIndex;',
			'	attribute vec4 skinWeight;',

			'#endif',

			'\n'

		].filter( filterEmptyLine ).join( '\n' );

		prefixFragment = [

			customExtensions,

			'precision ' + parameters.precision + ' float;',
			'precision ' + parameters.precision + ' int;',

			'#define SHADER_NAME ' + material.__webglShader.name,

			customDefines,

			parameters.alphaTest ? '#define ALPHATEST ' + parameters.alphaTest : '',

			'#define GAMMA_FACTOR ' + gammaFactorDefine,

			( parameters.useFog && parameters.fog ) ? '#define USE_FOG' : '',
			( parameters.useFog && parameters.fogExp ) ? '#define FOG_EXP2' : '',

			parameters.map ? '#define USE_MAP' : '',
			parameters.envMap ? '#define USE_ENVMAP' : '',
			parameters.envMap ? '#define ' + envMapTypeDefine : '',
			parameters.envMap ? '#define ' + envMapModeDefine : '',
			parameters.envMap ? '#define ' + envMapBlendingDefine : '',
			parameters.lightMap ? '#define USE_LIGHTMAP' : '',
			parameters.aoMap ? '#define USE_AOMAP' : '',
			parameters.emissiveMap ? '#define USE_EMISSIVEMAP' : '',
			parameters.bumpMap ? '#define USE_BUMPMAP' : '',
			parameters.normalMap ? '#define USE_NORMALMAP' : '',
			parameters.specularMap ? '#define USE_SPECULARMAP' : '',
			parameters.roughnessMap ? '#define USE_ROUGHNESSMAP' : '',
			parameters.metalnessMap ? '#define USE_METALNESSMAP' : '',
			parameters.alphaMap ? '#define USE_ALPHAMAP' : '',
			parameters.vertexColors ? '#define USE_COLOR' : '',

			parameters.gradientMap ? '#define USE_GRADIENTMAP' : '',

			parameters.flatShading ? '#define FLAT_SHADED' : '',

			parameters.doubleSided ? '#define DOUBLE_SIDED' : '',
			parameters.flipSided ? '#define FLIP_SIDED' : '',

			'#define NUM_CLIPPING_PLANES ' + parameters.numClippingPlanes,
			'#define UNION_CLIPPING_PLANES ' + (parameters.numClippingPlanes - parameters.numClipIntersection),

			parameters.shadowMapEnabled ? '#define USE_SHADOWMAP' : '',
			parameters.shadowMapEnabled ? '#define ' + shadowMapTypeDefine : '',

			parameters.premultipliedAlpha ? "#define PREMULTIPLIED_ALPHA" : '',

			parameters.physicallyCorrectLights ? "#define PHYSICALLY_CORRECT_LIGHTS" : '',

			parameters.logarithmicDepthBuffer ? '#define USE_LOGDEPTHBUF' : '',
			parameters.logarithmicDepthBuffer && renderer.extensions.get( 'EXT_frag_depth' ) ? '#define USE_LOGDEPTHBUF_EXT' : '',

			parameters.envMap && renderer.extensions.get( 'EXT_shader_texture_lod' ) ? '#define TEXTURE_LOD_EXT' : '',

			'uniform mat4 viewMatrix;',
			'uniform vec3 cameraPosition;',

			( parameters.toneMapping !== NoToneMapping ) ? "#define TONE_MAPPING" : '',
			( parameters.toneMapping !== NoToneMapping ) ? ShaderChunk[ 'tonemapping_pars_fragment' ] : '',  // this code is required here because it is used by the toneMapping() function defined below
			( parameters.toneMapping !== NoToneMapping ) ? getToneMappingFunction( "toneMapping", parameters.toneMapping ) : '',

			( parameters.outputEncoding || parameters.mapEncoding || parameters.envMapEncoding || parameters.emissiveMapEncoding ) ? ShaderChunk[ 'encodings_pars_fragment' ] : '', // this code is required here because it is used by the various encoding/decoding function defined below
			parameters.mapEncoding ? getTexelDecodingFunction( 'mapTexelToLinear', parameters.mapEncoding ) : '',
			parameters.envMapEncoding ? getTexelDecodingFunction( 'envMapTexelToLinear', parameters.envMapEncoding ) : '',
			parameters.emissiveMapEncoding ? getTexelDecodingFunction( 'emissiveMapTexelToLinear', parameters.emissiveMapEncoding ) : '',
			parameters.outputEncoding ? getTexelEncodingFunction( "linearToOutputTexel", parameters.outputEncoding ) : '',

			parameters.depthPacking ? "#define DEPTH_PACKING " + material.depthPacking : '',

			'\n'

		].filter( filterEmptyLine ).join( '\n' );

	}

	vertexShader = parseIncludes( vertexShader, parameters );
	vertexShader = replaceLightNums( vertexShader, parameters );

	fragmentShader = parseIncludes( fragmentShader, parameters );
	fragmentShader = replaceLightNums( fragmentShader, parameters );

	if ( ! material.isShaderMaterial ) {

		vertexShader = unrollLoops( vertexShader );
		fragmentShader = unrollLoops( fragmentShader );

	}

	var vertexGlsl = prefixVertex + vertexShader;
	var fragmentGlsl = prefixFragment + fragmentShader;

	// console.log( '*VERTEX*', vertexGlsl );
	// console.log( '*FRAGMENT*', fragmentGlsl );

	var glVertexShader = WebGLShader( gl, gl.VERTEX_SHADER, vertexGlsl );
	var glFragmentShader = WebGLShader( gl, gl.FRAGMENT_SHADER, fragmentGlsl );

	gl.attachShader( program, glVertexShader );
	gl.attachShader( program, glFragmentShader );

	// Force a particular attribute to index 0.

	if ( material.index0AttributeName !== undefined ) {

		gl.bindAttribLocation( program, 0, material.index0AttributeName );

	} else if ( parameters.morphTargets === true ) {

		// programs with morphTargets displace position out of attribute 0
		gl.bindAttribLocation( program, 0, 'position' );

	}

	gl.linkProgram( program );

	var programLog = gl.getProgramInfoLog( program );
	var vertexLog = gl.getShaderInfoLog( glVertexShader );
	var fragmentLog = gl.getShaderInfoLog( glFragmentShader );

	var runnable = true;
	var haveDiagnostics = true;

	// console.log( '**VERTEX**', gl.getExtension( 'WEBGL_debug_shaders' ).getTranslatedShaderSource( glVertexShader ) );
	// console.log( '**FRAGMENT**', gl.getExtension( 'WEBGL_debug_shaders' ).getTranslatedShaderSource( glFragmentShader ) );

	if ( gl.getProgramParameter( program, gl.LINK_STATUS ) === false ) {

		runnable = false;

		console.error( 'THREE.WebGLProgram: shader error: ', gl.getError(), 'gl.VALIDATE_STATUS', gl.getProgramParameter( program, gl.VALIDATE_STATUS ), 'gl.getProgramInfoLog', programLog, vertexLog, fragmentLog );

	} else if ( programLog !== '' ) {

		console.warn( 'THREE.WebGLProgram: gl.getProgramInfoLog()', programLog );

	} else if ( vertexLog === '' || fragmentLog === '' ) {

		haveDiagnostics = false;

	}

	if ( haveDiagnostics ) {

		this.diagnostics = {

			runnable: runnable,
			material: material,

			programLog: programLog,

			vertexShader: {

				log: vertexLog,
				prefix: prefixVertex

			},

			fragmentShader: {

				log: fragmentLog,
				prefix: prefixFragment

			}

		};

	}

	// clean up

	gl.deleteShader( glVertexShader );
	gl.deleteShader( glFragmentShader );

	// set up caching for uniform locations

	var cachedUniforms;

	this.getUniforms = function() {

		if ( cachedUniforms === undefined ) {

			cachedUniforms =
				new WebGLUniforms( gl, program, renderer );

		}

		return cachedUniforms;

	};

	// set up caching for attribute locations

	var cachedAttributes;

	this.getAttributes = function() {

		if ( cachedAttributes === undefined ) {

			cachedAttributes = fetchAttributeLocations( gl, program );

		}

		return cachedAttributes;

	};

	// free resource

	this.destroy = function() {

		gl.deleteProgram( program );
		this.program = undefined;

	};

	// DEPRECATED

	Object.defineProperties( this, {

		uniforms: {
			get: function() {

				console.warn( 'THREE.WebGLProgram: .uniforms is now .getUniforms().' );
				return this.getUniforms();

			}
		},

		attributes: {
			get: function() {

				console.warn( 'THREE.WebGLProgram: .attributes is now .getAttributes().' );
				return this.getAttributes();

			}
		}

	} );


	//

	this.id = programIdCount ++;
	this.code = code;
	this.usedTimes = 1;
	this.program = program;
	this.vertexShader = glVertexShader;
	this.fragmentShader = glFragmentShader;

	return this;

}

/**
 * @author mrdoob / http://mrdoob.com/
 */

function WebGLPrograms( renderer, capabilities ) {

	var programs = [];

	var shaderIDs = {
		MeshDepthMaterial: 'depth',
		MeshNormalMaterial: 'normal',
		MeshBasicMaterial: 'basic',
		MeshLambertMaterial: 'lambert',
		MeshPhongMaterial: 'phong',
		MeshToonMaterial: 'phong',
		MeshStandardMaterial: 'physical',
		MeshPhysicalMaterial: 'physical',
		LineBasicMaterial: 'basic',
		LineDashedMaterial: 'dashed',
		PointsMaterial: 'points'
	};

	var parameterNames = [
		"precision", "supportsVertexTextures", "map", "mapEncoding", "envMap", "envMapMode", "envMapEncoding",
		"lightMap", "aoMap", "emissiveMap", "emissiveMapEncoding", "bumpMap", "normalMap", "displacementMap", "specularMap",
		"roughnessMap", "metalnessMap", "gradientMap",
		"alphaMap", "combine", "vertexColors", "fog", "useFog", "fogExp",
		"flatShading", "sizeAttenuation", "logarithmicDepthBuffer", "skinning",
		"maxBones", "useVertexTexture", "morphTargets", "morphNormals",
		"maxMorphTargets", "maxMorphNormals", "premultipliedAlpha",
		"numDirLights", "numPointLights", "numSpotLights", "numHemiLights", "numRectAreaLights",
		"shadowMapEnabled", "shadowMapType", "toneMapping", 'physicallyCorrectLights',
		"alphaTest", "doubleSided", "flipSided", "numClippingPlanes", "numClipIntersection", "depthPacking"
	];


	function allocateBones( object ) {

		if ( capabilities.floatVertexTextures && object && object.skeleton && object.skeleton.useVertexTexture ) {

			return 1024;

		} else {

			// default for when object is not specified
			// ( for example when prebuilding shader to be used with multiple objects )
			//
			//  - leave some extra space for other uniforms
			//  - limit here is ANGLE's 254 max uniform vectors
			//    (up to 54 should be safe)

			var nVertexUniforms = capabilities.maxVertexUniforms;
			var nVertexMatrices = Math.floor( ( nVertexUniforms - 20 ) / 4 );

			var maxBones = nVertexMatrices;

			if ( object !== undefined && (object && object.isSkinnedMesh) ) {

				maxBones = Math.min( object.skeleton.bones.length, maxBones );

				if ( maxBones < object.skeleton.bones.length ) {

					console.warn( 'WebGLRenderer: too many bones - ' + object.skeleton.bones.length + ', this GPU supports just ' + maxBones + ' (try OpenGL instead of ANGLE)' );

				}

			}

			return maxBones;

		}

	}

	function getTextureEncodingFromMap( map, gammaOverrideLinear ) {

		var encoding;

		if ( ! map ) {

			encoding = LinearEncoding;

		} else if ( map.isTexture ) {

			encoding = map.encoding;

		} else if ( map.isWebGLRenderTarget ) {

			console.warn( "THREE.WebGLPrograms.getTextureEncodingFromMap: don't use render targets as textures. Use their .texture property instead." );
			encoding = map.texture.encoding;

		}

		// add backwards compatibility for WebGLRenderer.gammaInput/gammaOutput parameter, should probably be removed at some point.
		if ( encoding === LinearEncoding && gammaOverrideLinear ) {

			encoding = GammaEncoding;

		}

		return encoding;

	}

	this.getParameters = function ( material, lights, fog, nClipPlanes, nClipIntersection, object ) {

		var shaderID = shaderIDs[ material.type ];

		// heuristics to create shader parameters according to lights in the scene
		// (not to blow over maxLights budget)

		var maxBones = allocateBones( object );
		var precision = renderer.getPrecision();

		if ( material.precision !== null ) {

			precision = capabilities.getMaxPrecision( material.precision );

			if ( precision !== material.precision ) {

				console.warn( 'THREE.WebGLProgram.getParameters:', material.precision, 'not supported, using', precision, 'instead.' );

			}

		}

		var currentRenderTarget = renderer.getCurrentRenderTarget();

		var parameters = {

			shaderID: shaderID,

			precision: precision,
			supportsVertexTextures: capabilities.vertexTextures,
			outputEncoding: getTextureEncodingFromMap( ( ! currentRenderTarget ) ? null : currentRenderTarget.texture, renderer.gammaOutput ),
			map: !! material.map,
			mapEncoding: getTextureEncodingFromMap( material.map, renderer.gammaInput ),
			envMap: !! material.envMap,
			envMapMode: material.envMap && material.envMap.mapping,
			envMapEncoding: getTextureEncodingFromMap( material.envMap, renderer.gammaInput ),
			envMapCubeUV: ( !! material.envMap ) && ( ( material.envMap.mapping === CubeUVReflectionMapping ) || ( material.envMap.mapping === CubeUVRefractionMapping ) ),
			lightMap: !! material.lightMap,
			aoMap: !! material.aoMap,
			emissiveMap: !! material.emissiveMap,
			emissiveMapEncoding: getTextureEncodingFromMap( material.emissiveMap, renderer.gammaInput ),
			bumpMap: !! material.bumpMap,
			normalMap: !! material.normalMap,
			displacementMap: !! material.displacementMap,
			roughnessMap: !! material.roughnessMap,
			metalnessMap: !! material.metalnessMap,
			specularMap: !! material.specularMap,
			alphaMap: !! material.alphaMap,

			gradientMap: !! material.gradientMap,

			combine: material.combine,

			vertexColors: material.vertexColors,

			fog: !! fog,
			useFog: material.fog,
			fogExp: (fog && fog.isFogExp2),

			flatShading: material.shading === FlatShading,

			sizeAttenuation: material.sizeAttenuation,
			logarithmicDepthBuffer: capabilities.logarithmicDepthBuffer,

			skinning: material.skinning,
			maxBones: maxBones,
			useVertexTexture: capabilities.floatVertexTextures && object && object.skeleton && object.skeleton.useVertexTexture,

			morphTargets: material.morphTargets,
			morphNormals: material.morphNormals,
			maxMorphTargets: renderer.maxMorphTargets,
			maxMorphNormals: renderer.maxMorphNormals,

			numDirLights: lights.directional.length,
			numPointLights: lights.point.length,
			numSpotLights: lights.spot.length,
			numRectAreaLights: lights.rectArea.length,
			numHemiLights: lights.hemi.length,

			numClippingPlanes: nClipPlanes,
			numClipIntersection: nClipIntersection,

			shadowMapEnabled: renderer.shadowMap.enabled && object.receiveShadow && lights.shadows.length > 0,
			shadowMapType: renderer.shadowMap.type,

			toneMapping: renderer.toneMapping,
			physicallyCorrectLights: renderer.physicallyCorrectLights,

			premultipliedAlpha: material.premultipliedAlpha,

			alphaTest: material.alphaTest,
			doubleSided: material.side === DoubleSide,
			flipSided: material.side === BackSide,

			depthPacking: ( material.depthPacking !== undefined ) ? material.depthPacking : false

		};

		return parameters;

	};

	this.getProgramCode = function ( material, parameters ) {

		var array = [];

		if ( parameters.shaderID ) {

			array.push( parameters.shaderID );

		} else {

			array.push( material.fragmentShader );
			array.push( material.vertexShader );

		}

		if ( material.defines !== undefined ) {

			for ( var name in material.defines ) {

				array.push( name );
				array.push( material.defines[ name ] );

			}

		}

		for ( var i = 0; i < parameterNames.length; i ++ ) {

			array.push( parameters[ parameterNames[ i ] ] );

		}

		return array.join();

	};

	this.acquireProgram = function ( material, parameters, code ) {

		var program;

		// Check if code has been already compiled
		for ( var p = 0, pl = programs.length; p < pl; p ++ ) {

			var programInfo = programs[ p ];

			if ( programInfo.code === code ) {

				program = programInfo;
				++ program.usedTimes;

				break;

			}

		}

		if ( program === undefined ) {

			program = new WebGLProgram( renderer, code, material, parameters );
			programs.push( program );

		}

		return program;

	};

	this.releaseProgram = function( program ) {

		if ( -- program.usedTimes === 0 ) {

			// Remove from unordered set
			var i = programs.indexOf( program );
			programs[ i ] = programs[ programs.length - 1 ];
			programs.pop();

			// Free WebGL resources
			program.destroy();

		}

	};

	// Exposed for resource monitoring & error feedback via renderer.info:
	this.programs = programs;

}

/**
 * @author mrdoob / http://mrdoob.com/
 */

function WebGLGeometries( gl, properties, info ) {

	var geometries = {};

	function onGeometryDispose( event ) {

		var geometry = event.target;
		var buffergeometry = geometries[ geometry.id ];

		if ( buffergeometry.index !== null ) {

			deleteAttribute( buffergeometry.index );

		}

		deleteAttributes( buffergeometry.attributes );

		geometry.removeEventListener( 'dispose', onGeometryDispose );

		delete geometries[ geometry.id ];

		// TODO

		var property = properties.get( geometry );

		if ( property.wireframe ) {

			deleteAttribute( property.wireframe );

		}

		properties.delete( geometry );

		var bufferproperty = properties.get( buffergeometry );

		if ( bufferproperty.wireframe ) {

			deleteAttribute( bufferproperty.wireframe );

		}

		properties.delete( buffergeometry );

		//

		info.memory.geometries --;

	}

	function getAttributeBuffer( attribute ) {

		if ( attribute.isInterleavedBufferAttribute ) {

			return properties.get( attribute.data ).__webglBuffer;

		}

		return properties.get( attribute ).__webglBuffer;

	}

	function deleteAttribute( attribute ) {

		var buffer = getAttributeBuffer( attribute );

		if ( buffer !== undefined ) {

			gl.deleteBuffer( buffer );
			removeAttributeBuffer( attribute );

		}

	}

	function deleteAttributes( attributes ) {

		for ( var name in attributes ) {

			deleteAttribute( attributes[ name ] );

		}

	}

	function removeAttributeBuffer( attribute ) {

		if ( attribute.isInterleavedBufferAttribute ) {

			properties.delete( attribute.data );

		} else {

			properties.delete( attribute );

		}

	}

	return {

		get: function ( object ) {

			var geometry = object.geometry;

			if ( geometries[ geometry.id ] !== undefined ) {

				return geometries[ geometry.id ];

			}

			geometry.addEventListener( 'dispose', onGeometryDispose );

			var buffergeometry;

			if ( geometry.isBufferGeometry ) {

				buffergeometry = geometry;

			} else if ( geometry.isGeometry ) {

				if ( geometry._bufferGeometry === undefined ) {

					geometry._bufferGeometry = new BufferGeometry().setFromObject( object );

				}

				buffergeometry = geometry._bufferGeometry;

			}

			geometries[ geometry.id ] = buffergeometry;

			info.memory.geometries ++;

			return buffergeometry;

		}

	};

}

/**
 * @author mrdoob / http://mrdoob.com/
 */

function WebGLObjects( gl, properties, info ) {

	var geometries = new WebGLGeometries( gl, properties, info );

	//

	function update( object ) {

		// TODO: Avoid updating twice (when using shadowMap). Maybe add frame counter.

		var geometry = geometries.get( object );

		if ( object.geometry.isGeometry ) {

			geometry.updateFromObject( object );

		}

		var index = geometry.index;
		var attributes = geometry.attributes;

		if ( index !== null ) {

			updateAttribute( index, gl.ELEMENT_ARRAY_BUFFER );

		}

		for ( var name in attributes ) {

			updateAttribute( attributes[ name ], gl.ARRAY_BUFFER );

		}

		// morph targets

		var morphAttributes = geometry.morphAttributes;

		for ( var name in morphAttributes ) {

			var array = morphAttributes[ name ];

			for ( var i = 0, l = array.length; i < l; i ++ ) {

				updateAttribute( array[ i ], gl.ARRAY_BUFFER );

			}

		}

		return geometry;

	}

	function updateAttribute( attribute, bufferType ) {

		var data = ( attribute.isInterleavedBufferAttribute ) ? attribute.data : attribute;

		var attributeProperties = properties.get( data );

		if ( attributeProperties.__webglBuffer === undefined ) {

			createBuffer( attributeProperties, data, bufferType );

		} else if ( attributeProperties.version !== data.version ) {

			updateBuffer( attributeProperties, data, bufferType );

		}

	}

	function createBuffer( attributeProperties, data, bufferType ) {

		attributeProperties.__webglBuffer = gl.createBuffer();
		gl.bindBuffer( bufferType, attributeProperties.__webglBuffer );

		var usage = data.dynamic ? gl.DYNAMIC_DRAW : gl.STATIC_DRAW;

		gl.bufferData( bufferType, data.array, usage );

		var type = gl.FLOAT;
		var array = data.array;

		if ( array instanceof Float32Array ) {

			type = gl.FLOAT;

		} else if ( array instanceof Float64Array ) {

			console.warn( "Unsupported data buffer format: Float64Array" );

		} else if ( array instanceof Uint16Array ) {

			type = gl.UNSIGNED_SHORT;

		} else if ( array instanceof Int16Array ) {

			type = gl.SHORT;

		} else if ( array instanceof Uint32Array ) {

			type = gl.UNSIGNED_INT;

		} else if ( array instanceof Int32Array ) {

			type = gl.INT;

		} else if ( array instanceof Int8Array ) {

			type = gl.BYTE;

		} else if ( array instanceof Uint8Array ) {

			type = gl.UNSIGNED_BYTE;

		}

		attributeProperties.bytesPerElement = array.BYTES_PER_ELEMENT;
		attributeProperties.type = type;
		attributeProperties.version = data.version;

		data.onUploadCallback();

	}

	function updateBuffer( attributeProperties, data, bufferType ) {

		gl.bindBuffer( bufferType, attributeProperties.__webglBuffer );

		if ( data.dynamic === false ) {

			gl.bufferData( bufferType, data.array, gl.STATIC_DRAW );

		} else if ( data.updateRange.count === - 1 ) {

			// Not using update ranges

			gl.bufferSubData( bufferType, 0, data.array );

		} else if ( data.updateRange.count === 0 ) {

			console.error( 'THREE.WebGLObjects.updateBuffer: dynamic THREE.BufferAttribute marked as needsUpdate but updateRange.count is 0, ensure you are using set methods or updating manually.' );

		} else {

			gl.bufferSubData( bufferType, data.updateRange.offset * data.array.BYTES_PER_ELEMENT,
							  data.array.subarray( data.updateRange.offset, data.updateRange.offset + data.updateRange.count ) );

			data.updateRange.count = 0; // reset range

		}

		attributeProperties.version = data.version;

	}

	function getAttributeBuffer( attribute ) {

		if ( attribute.isInterleavedBufferAttribute ) {

			return properties.get( attribute.data ).__webglBuffer;

		}

		return properties.get( attribute ).__webglBuffer;

	}

	function getAttributeProperties( attribute ) {

		if ( attribute.isInterleavedBufferAttribute ) {

			return properties.get( attribute.data );

		}

		return properties.get( attribute );

	}

	function getWireframeAttribute( geometry ) {

		var property = properties.get( geometry );

		if ( property.wireframe !== undefined ) {

			return property.wireframe;

		}

		var indices = [];

		var index = geometry.index;
		var attributes = geometry.attributes;

		// console.time( 'wireframe' );

		if ( index !== null ) {

			var array = index.array;

			for ( var i = 0, l = array.length; i < l; i += 3 ) {

				var a = array[ i + 0 ];
				var b = array[ i + 1 ];
				var c = array[ i + 2 ];

				indices.push( a, b, b, c, c, a );

			}

		} else {

			var array = attributes.position.array;

			for ( var i = 0, l = ( array.length / 3 ) - 1; i < l; i += 3 ) {

				var a = i + 0;
				var b = i + 1;
				var c = i + 2;

				indices.push( a, b, b, c, c, a );

			}

		}

		// console.timeEnd( 'wireframe' );

		var attribute = new ( arrayMax( indices ) > 65535 ? Uint32BufferAttribute : Uint16BufferAttribute )( indices, 1 );

		updateAttribute( attribute, gl.ELEMENT_ARRAY_BUFFER );

		property.wireframe = attribute;

		return attribute;

	}

	return {

		getAttributeBuffer: getAttributeBuffer,
		getAttributeProperties: getAttributeProperties,
		getWireframeAttribute: getWireframeAttribute,

		update: update

	};

}

/**
 * @author mrdoob / http://mrdoob.com/
 */

function WebGLTextures( _gl, extensions, state, properties, capabilities, paramThreeToGL, info ) {

	var _infoMemory = info.memory;
	var _isWebGL2 = ( typeof WebGL2RenderingContext !== 'undefined' && _gl instanceof WebGL2RenderingContext );

	//

	function clampToMaxSize( image, maxSize ) {

		if ( image.width > maxSize || image.height > maxSize ) {

			// Warning: Scaling through the canvas will only work with images that use
			// premultiplied alpha.

			var scale = maxSize / Math.max( image.width, image.height );

			var canvas = document.createElementNS( 'http://www.w3.org/1999/xhtml', 'canvas' );
			canvas.width = Math.floor( image.width * scale );
			canvas.height = Math.floor( image.height * scale );

			var context = canvas.getContext( '2d' );
			context.drawImage( image, 0, 0, image.width, image.height, 0, 0, canvas.width, canvas.height );

			console.warn( 'THREE.WebGLRenderer: image is too big (' + image.width + 'x' + image.height + '). Resized to ' + canvas.width + 'x' + canvas.height, image );

			return canvas;

		}

		return image;

	}

	function isPowerOfTwo( image ) {

		return _Math.isPowerOfTwo( image.width ) && _Math.isPowerOfTwo( image.height );

	}

	function makePowerOfTwo( image ) {

		if ( image instanceof HTMLImageElement || image instanceof HTMLCanvasElement ) {

			var canvas = document.createElementNS( 'http://www.w3.org/1999/xhtml', 'canvas' );
			canvas.width = _Math.nearestPowerOfTwo( image.width );
			canvas.height = _Math.nearestPowerOfTwo( image.height );

			var context = canvas.getContext( '2d' );
			context.drawImage( image, 0, 0, canvas.width, canvas.height );

			console.warn( 'THREE.WebGLRenderer: image is not power of two (' + image.width + 'x' + image.height + '). Resized to ' + canvas.width + 'x' + canvas.height, image );

			return canvas;

		}

		return image;

	}

	function textureNeedsPowerOfTwo( texture ) {

		return ( texture.wrapS !== ClampToEdgeWrapping || texture.wrapT !== ClampToEdgeWrapping ) ||
			( texture.minFilter !== NearestFilter && texture.minFilter !== LinearFilter );

	}

	// Fallback filters for non-power-of-2 textures

	function filterFallback( f ) {

		if ( f === NearestFilter || f === NearestMipMapNearestFilter || f === NearestMipMapLinearFilter ) {

			return _gl.NEAREST;

		}

		return _gl.LINEAR;

	}

	//

	function onTextureDispose( event ) {

		var texture = event.target;

		texture.removeEventListener( 'dispose', onTextureDispose );

		deallocateTexture( texture );

		_infoMemory.textures --;


	}

	function onRenderTargetDispose( event ) {

		var renderTarget = event.target;

		renderTarget.removeEventListener( 'dispose', onRenderTargetDispose );

		deallocateRenderTarget( renderTarget );

		_infoMemory.textures --;

	}

	//

	function deallocateTexture( texture ) {

		var textureProperties = properties.get( texture );

		if ( texture.image && textureProperties.__image__webglTextureCube ) {

			// cube texture

			_gl.deleteTexture( textureProperties.__image__webglTextureCube );

		} else {

			// 2D texture

			if ( textureProperties.__webglInit === undefined ) return;

			_gl.deleteTexture( textureProperties.__webglTexture );

		}

		// remove all webgl properties
		properties.delete( texture );

	}

	function deallocateRenderTarget( renderTarget ) {

		var renderTargetProperties = properties.get( renderTarget );
		var textureProperties = properties.get( renderTarget.texture );

		if ( ! renderTarget ) return;

		if ( textureProperties.__webglTexture !== undefined ) {

			_gl.deleteTexture( textureProperties.__webglTexture );

		}

		if ( renderTarget.depthTexture ) {

			renderTarget.depthTexture.dispose();

		}

		if ( renderTarget.isWebGLRenderTargetCube ) {

			for ( var i = 0; i < 6; i ++ ) {

				_gl.deleteFramebuffer( renderTargetProperties.__webglFramebuffer[ i ] );
				if ( renderTargetProperties.__webglDepthbuffer ) _gl.deleteRenderbuffer( renderTargetProperties.__webglDepthbuffer[ i ] );

			}

		} else {

			_gl.deleteFramebuffer( renderTargetProperties.__webglFramebuffer );
			if ( renderTargetProperties.__webglDepthbuffer ) _gl.deleteRenderbuffer( renderTargetProperties.__webglDepthbuffer );

		}

		properties.delete( renderTarget.texture );
		properties.delete( renderTarget );

	}

	//



	function setTexture2D( texture, slot ) {

		var textureProperties = properties.get( texture );

		if ( texture.version > 0 && textureProperties.__version !== texture.version ) {

			var image = texture.image;

			if ( image === undefined ) {

				console.warn( 'THREE.WebGLRenderer: Texture marked for update but image is undefined', texture );

			} else if ( image.complete === false ) {

				console.warn( 'THREE.WebGLRenderer: Texture marked for update but image is incomplete', texture );

			} else {

				uploadTexture( textureProperties, texture, slot );
				return;

			}

		}

		state.activeTexture( _gl.TEXTURE0 + slot );
		state.bindTexture( _gl.TEXTURE_2D, textureProperties.__webglTexture );

	}

	function setTextureCube( texture, slot ) {

		var textureProperties = properties.get( texture );

		if ( texture.image.length === 6 ) {

			if ( texture.version > 0 && textureProperties.__version !== texture.version ) {

				if ( ! textureProperties.__image__webglTextureCube ) {

					texture.addEventListener( 'dispose', onTextureDispose );

					textureProperties.__image__webglTextureCube = _gl.createTexture();

					_infoMemory.textures ++;

				}

				state.activeTexture( _gl.TEXTURE0 + slot );
				state.bindTexture( _gl.TEXTURE_CUBE_MAP, textureProperties.__image__webglTextureCube );

				_gl.pixelStorei( _gl.UNPACK_FLIP_Y_WEBGL, texture.flipY );

				var isCompressed = ( texture && texture.isCompressedTexture );
				var isDataTexture = ( texture.image[ 0 ] && texture.image[ 0 ].isDataTexture );

				var cubeImage = [];

				for ( var i = 0; i < 6; i ++ ) {

					if ( ! isCompressed && ! isDataTexture ) {

						cubeImage[ i ] = clampToMaxSize( texture.image[ i ], capabilities.maxCubemapSize );

					} else {

						cubeImage[ i ] = isDataTexture ? texture.image[ i ].image : texture.image[ i ];

					}

				}

				var image = cubeImage[ 0 ],
				isPowerOfTwoImage = isPowerOfTwo( image ),
				glFormat = paramThreeToGL( texture.format ),
				glType = paramThreeToGL( texture.type );

				setTextureParameters( _gl.TEXTURE_CUBE_MAP, texture, isPowerOfTwoImage );

				for ( var i = 0; i < 6; i ++ ) {

					if ( ! isCompressed ) {

						if ( isDataTexture ) {

							state.texImage2D( _gl.TEXTURE_CUBE_MAP_POSITIVE_X + i, 0, glFormat, cubeImage[ i ].width, cubeImage[ i ].height, 0, glFormat, glType, cubeImage[ i ].data );

						} else {

							state.texImage2D( _gl.TEXTURE_CUBE_MAP_POSITIVE_X + i, 0, glFormat, glFormat, glType, cubeImage[ i ] );

						}

					} else {

						var mipmap, mipmaps = cubeImage[ i ].mipmaps;

						for ( var j = 0, jl = mipmaps.length; j < jl; j ++ ) {

							mipmap = mipmaps[ j ];

							if ( texture.format !== RGBAFormat && texture.format !== RGBFormat ) {

								if ( state.getCompressedTextureFormats().indexOf( glFormat ) > - 1 ) {

									state.compressedTexImage2D( _gl.TEXTURE_CUBE_MAP_POSITIVE_X + i, j, glFormat, mipmap.width, mipmap.height, 0, mipmap.data );

								} else {

									console.warn( "THREE.WebGLRenderer: Attempt to load unsupported compressed texture format in .setTextureCube()" );

								}

							} else {

								state.texImage2D( _gl.TEXTURE_CUBE_MAP_POSITIVE_X + i, j, glFormat, mipmap.width, mipmap.height, 0, glFormat, glType, mipmap.data );

							}

						}

					}

				}

				if ( texture.generateMipmaps && isPowerOfTwoImage ) {

					_gl.generateMipmap( _gl.TEXTURE_CUBE_MAP );

				}

				textureProperties.__version = texture.version;

				if ( texture.onUpdate ) texture.onUpdate( texture );

			} else {

				state.activeTexture( _gl.TEXTURE0 + slot );
				state.bindTexture( _gl.TEXTURE_CUBE_MAP, textureProperties.__image__webglTextureCube );

			}

		}

	}

	function setTextureCubeDynamic( texture, slot ) {

		state.activeTexture( _gl.TEXTURE0 + slot );
		state.bindTexture( _gl.TEXTURE_CUBE_MAP, properties.get( texture ).__webglTexture );

	}

	function setTextureParameters( textureType, texture, isPowerOfTwoImage ) {

		var extension;

		if ( isPowerOfTwoImage ) {

			_gl.texParameteri( textureType, _gl.TEXTURE_WRAP_S, paramThreeToGL( texture.wrapS ) );
			_gl.texParameteri( textureType, _gl.TEXTURE_WRAP_T, paramThreeToGL( texture.wrapT ) );

			_gl.texParameteri( textureType, _gl.TEXTURE_MAG_FILTER, paramThreeToGL( texture.magFilter ) );
			_gl.texParameteri( textureType, _gl.TEXTURE_MIN_FILTER, paramThreeToGL( texture.minFilter ) );

		} else {

			_gl.texParameteri( textureType, _gl.TEXTURE_WRAP_S, _gl.CLAMP_TO_EDGE );
			_gl.texParameteri( textureType, _gl.TEXTURE_WRAP_T, _gl.CLAMP_TO_EDGE );

			if ( texture.wrapS !== ClampToEdgeWrapping || texture.wrapT !== ClampToEdgeWrapping ) {

				console.warn( 'THREE.WebGLRenderer: Texture is not power of two. Texture.wrapS and Texture.wrapT should be set to THREE.ClampToEdgeWrapping.', texture );

			}

			_gl.texParameteri( textureType, _gl.TEXTURE_MAG_FILTER, filterFallback( texture.magFilter ) );
			_gl.texParameteri( textureType, _gl.TEXTURE_MIN_FILTER, filterFallback( texture.minFilter ) );

			if ( texture.minFilter !== NearestFilter && texture.minFilter !== LinearFilter ) {

				console.warn( 'THREE.WebGLRenderer: Texture is not power of two. Texture.minFilter should be set to THREE.NearestFilter or THREE.LinearFilter.', texture );

			}

		}

		extension = extensions.get( 'EXT_texture_filter_anisotropic' );

		if ( extension ) {

			if ( texture.type === FloatType && extensions.get( 'OES_texture_float_linear' ) === null ) return;
			if ( texture.type === HalfFloatType && extensions.get( 'OES_texture_half_float_linear' ) === null ) return;

			if ( texture.anisotropy > 1 || properties.get( texture ).__currentAnisotropy ) {

				_gl.texParameterf( textureType, extension.TEXTURE_MAX_ANISOTROPY_EXT, Math.min( texture.anisotropy, capabilities.getMaxAnisotropy() ) );
				properties.get( texture ).__currentAnisotropy = texture.anisotropy;

			}

		}

	}

	function uploadTexture( textureProperties, texture, slot ) {

		if ( textureProperties.__webglInit === undefined ) {

			textureProperties.__webglInit = true;

			texture.addEventListener( 'dispose', onTextureDispose );

			textureProperties.__webglTexture = _gl.createTexture();

			_infoMemory.textures ++;

		}

		state.activeTexture( _gl.TEXTURE0 + slot );
		state.bindTexture( _gl.TEXTURE_2D, textureProperties.__webglTexture );

		_gl.pixelStorei( _gl.UNPACK_FLIP_Y_WEBGL, texture.flipY );
		_gl.pixelStorei( _gl.UNPACK_PREMULTIPLY_ALPHA_WEBGL, texture.premultiplyAlpha );
		_gl.pixelStorei( _gl.UNPACK_ALIGNMENT, texture.unpackAlignment );

		var image = clampToMaxSize( texture.image, capabilities.maxTextureSize );

		if ( textureNeedsPowerOfTwo( texture ) && isPowerOfTwo( image ) === false ) {

			image = makePowerOfTwo( image );

		}

		var isPowerOfTwoImage = isPowerOfTwo( image ),
		glFormat = paramThreeToGL( texture.format ),
		glType = paramThreeToGL( texture.type );

		setTextureParameters( _gl.TEXTURE_2D, texture, isPowerOfTwoImage );

		var mipmap, mipmaps = texture.mipmaps;

		if ( texture.isDepthTexture ) {

			// populate depth texture with dummy data

			var internalFormat = _gl.DEPTH_COMPONENT;

			if ( texture.type === FloatType ) {

				if ( !_isWebGL2 ) throw new Error('Float Depth Texture only supported in WebGL2.0');
				internalFormat = _gl.DEPTH_COMPONENT32F;

			} else if ( _isWebGL2 ) {

				// WebGL 2.0 requires signed internalformat for glTexImage2D
				internalFormat = _gl.DEPTH_COMPONENT16;

			}

			if ( texture.format === DepthFormat && internalFormat === _gl.DEPTH_COMPONENT ) {

				// The error INVALID_OPERATION is generated by texImage2D if format and internalformat are
				// DEPTH_COMPONENT and type is not UNSIGNED_SHORT or UNSIGNED_INT
				// (https://www.khronos.org/registry/webgl/extensions/WEBGL_depth_texture/)
				if ( texture.type !== UnsignedShortType && texture.type !== UnsignedIntType ) {

				        console.warn( 'THREE.WebGLRenderer: Use UnsignedShortType or UnsignedIntType for DepthFormat DepthTexture.' );

					texture.type = UnsignedShortType;
					glType = paramThreeToGL( texture.type );

				}

			}

			// Depth stencil textures need the DEPTH_STENCIL internal format
			// (https://www.khronos.org/registry/webgl/extensions/WEBGL_depth_texture/)
			if ( texture.format === DepthStencilFormat ) {

				internalFormat = _gl.DEPTH_STENCIL;

				// The error INVALID_OPERATION is generated by texImage2D if format and internalformat are
				// DEPTH_STENCIL and type is not UNSIGNED_INT_24_8_WEBGL.
				// (https://www.khronos.org/registry/webgl/extensions/WEBGL_depth_texture/)
				if ( texture.type !== UnsignedInt248Type ) {

				        console.warn( 'THREE.WebGLRenderer: Use UnsignedInt248Type for DepthStencilFormat DepthTexture.' );

					texture.type = UnsignedInt248Type;
					glType = paramThreeToGL( texture.type );

				}

			}

			state.texImage2D( _gl.TEXTURE_2D, 0, internalFormat, image.width, image.height, 0, glFormat, glType, null );

		} else if ( texture.isDataTexture ) {

			// use manually created mipmaps if available
			// if there are no manual mipmaps
			// set 0 level mipmap and then use GL to generate other mipmap levels

			if ( mipmaps.length > 0 && isPowerOfTwoImage ) {

				for ( var i = 0, il = mipmaps.length; i < il; i ++ ) {

					mipmap = mipmaps[ i ];
					state.texImage2D( _gl.TEXTURE_2D, i, glFormat, mipmap.width, mipmap.height, 0, glFormat, glType, mipmap.data );

				}

				texture.generateMipmaps = false;

			} else {

				state.texImage2D( _gl.TEXTURE_2D, 0, glFormat, image.width, image.height, 0, glFormat, glType, image.data );

			}

		} else if ( texture.isCompressedTexture ) {

			for ( var i = 0, il = mipmaps.length; i < il; i ++ ) {

				mipmap = mipmaps[ i ];

				if ( texture.format !== RGBAFormat && texture.format !== RGBFormat ) {

					if ( state.getCompressedTextureFormats().indexOf( glFormat ) > - 1 ) {

						state.compressedTexImage2D( _gl.TEXTURE_2D, i, glFormat, mipmap.width, mipmap.height, 0, mipmap.data );

					} else {

						console.warn( "THREE.WebGLRenderer: Attempt to load unsupported compressed texture format in .uploadTexture()" );

					}

				} else {

					state.texImage2D( _gl.TEXTURE_2D, i, glFormat, mipmap.width, mipmap.height, 0, glFormat, glType, mipmap.data );

				}

			}

		} else {

			// regular Texture (image, video, canvas)

			// use manually created mipmaps if available
			// if there are no manual mipmaps
			// set 0 level mipmap and then use GL to generate other mipmap levels

			if ( mipmaps.length > 0 && isPowerOfTwoImage ) {

				for ( var i = 0, il = mipmaps.length; i < il; i ++ ) {

					mipmap = mipmaps[ i ];
					state.texImage2D( _gl.TEXTURE_2D, i, glFormat, glFormat, glType, mipmap );

				}

				texture.generateMipmaps = false;

			} else {

				state.texImage2D( _gl.TEXTURE_2D, 0, glFormat, glFormat, glType, image );

			}

		}

		if ( texture.generateMipmaps && isPowerOfTwoImage ) _gl.generateMipmap( _gl.TEXTURE_2D );

		textureProperties.__version = texture.version;

		if ( texture.onUpdate ) texture.onUpdate( texture );

	}

	// Render targets

	// Setup storage for target texture and bind it to correct framebuffer
	function setupFrameBufferTexture( framebuffer, renderTarget, attachment, textureTarget ) {

		var glFormat = paramThreeToGL( renderTarget.texture.format );
		var glType = paramThreeToGL( renderTarget.texture.type );
		state.texImage2D( textureTarget, 0, glFormat, renderTarget.width, renderTarget.height, 0, glFormat, glType, null );
		_gl.bindFramebuffer( _gl.FRAMEBUFFER, framebuffer );
		_gl.framebufferTexture2D( _gl.FRAMEBUFFER, attachment, textureTarget, properties.get( renderTarget.texture ).__webglTexture, 0 );
		_gl.bindFramebuffer( _gl.FRAMEBUFFER, null );

	}

	// Setup storage for internal depth/stencil buffers and bind to correct framebuffer
	function setupRenderBufferStorage( renderbuffer, renderTarget ) {

		_gl.bindRenderbuffer( _gl.RENDERBUFFER, renderbuffer );

		if ( renderTarget.depthBuffer && ! renderTarget.stencilBuffer ) {

			_gl.renderbufferStorage( _gl.RENDERBUFFER, _gl.DEPTH_COMPONENT16, renderTarget.width, renderTarget.height );
			_gl.framebufferRenderbuffer( _gl.FRAMEBUFFER, _gl.DEPTH_ATTACHMENT, _gl.RENDERBUFFER, renderbuffer );

		} else if ( renderTarget.depthBuffer && renderTarget.stencilBuffer ) {

			_gl.renderbufferStorage( _gl.RENDERBUFFER, _gl.DEPTH_STENCIL, renderTarget.width, renderTarget.height );
			_gl.framebufferRenderbuffer( _gl.FRAMEBUFFER, _gl.DEPTH_STENCIL_ATTACHMENT, _gl.RENDERBUFFER, renderbuffer );

		} else {

			// FIXME: We don't support !depth !stencil
			_gl.renderbufferStorage( _gl.RENDERBUFFER, _gl.RGBA4, renderTarget.width, renderTarget.height );

		}

		_gl.bindRenderbuffer( _gl.RENDERBUFFER, null );

	}

	// Setup resources for a Depth Texture for a FBO (needs an extension)
	function setupDepthTexture( framebuffer, renderTarget ) {

		var isCube = ( renderTarget && renderTarget.isWebGLRenderTargetCube );
		if ( isCube ) throw new Error('Depth Texture with cube render targets is not supported!');

		_gl.bindFramebuffer( _gl.FRAMEBUFFER, framebuffer );

		if ( !( renderTarget.depthTexture && renderTarget.depthTexture.isDepthTexture ) ) {

			throw new Error('renderTarget.depthTexture must be an instance of THREE.DepthTexture');

		}

		// upload an empty depth texture with framebuffer size
		if ( !properties.get( renderTarget.depthTexture ).__webglTexture ||
				renderTarget.depthTexture.image.width !== renderTarget.width ||
				renderTarget.depthTexture.image.height !== renderTarget.height ) {
			renderTarget.depthTexture.image.width = renderTarget.width;
			renderTarget.depthTexture.image.height = renderTarget.height;
			renderTarget.depthTexture.needsUpdate = true;
		}

		setTexture2D( renderTarget.depthTexture, 0 );

		var webglDepthTexture = properties.get( renderTarget.depthTexture ).__webglTexture;

		if ( renderTarget.depthTexture.format === DepthFormat ) {

			_gl.framebufferTexture2D( _gl.FRAMEBUFFER, _gl.DEPTH_ATTACHMENT, _gl.TEXTURE_2D, webglDepthTexture, 0 );

		} else if ( renderTarget.depthTexture.format === DepthStencilFormat ) {

			_gl.framebufferTexture2D( _gl.FRAMEBUFFER, _gl.DEPTH_STENCIL_ATTACHMENT, _gl.TEXTURE_2D, webglDepthTexture, 0 );

		} else {

			throw new Error('Unknown depthTexture format')

		}

	}

	// Setup GL resources for a non-texture depth buffer
	function setupDepthRenderbuffer( renderTarget ) {

		var renderTargetProperties = properties.get( renderTarget );

		var isCube = ( renderTarget.isWebGLRenderTargetCube === true );

		if ( renderTarget.depthTexture ) {

			if ( isCube ) throw new Error('target.depthTexture not supported in Cube render targets');

			setupDepthTexture( renderTargetProperties.__webglFramebuffer, renderTarget );

		} else {

			if ( isCube ) {

				renderTargetProperties.__webglDepthbuffer = [];

				for ( var i = 0; i < 6; i ++ ) {

					_gl.bindFramebuffer( _gl.FRAMEBUFFER, renderTargetProperties.__webglFramebuffer[ i ] );
					renderTargetProperties.__webglDepthbuffer[ i ] = _gl.createRenderbuffer();
					setupRenderBufferStorage( renderTargetProperties.__webglDepthbuffer[ i ], renderTarget );

				}

			} else {

				_gl.bindFramebuffer( _gl.FRAMEBUFFER, renderTargetProperties.__webglFramebuffer );
				renderTargetProperties.__webglDepthbuffer = _gl.createRenderbuffer();
				setupRenderBufferStorage( renderTargetProperties.__webglDepthbuffer, renderTarget );

			}

		}

		_gl.bindFramebuffer( _gl.FRAMEBUFFER, null );

	}

	// Set up GL resources for the render target
	function setupRenderTarget( renderTarget ) {

		var renderTargetProperties = properties.get( renderTarget );
		var textureProperties = properties.get( renderTarget.texture );

		renderTarget.addEventListener( 'dispose', onRenderTargetDispose );

		textureProperties.__webglTexture = _gl.createTexture();

		_infoMemory.textures ++;

		var isCube = ( renderTarget.isWebGLRenderTargetCube === true );
		var isTargetPowerOfTwo = isPowerOfTwo( renderTarget );

		// Setup framebuffer

		if ( isCube ) {

			renderTargetProperties.__webglFramebuffer = [];

			for ( var i = 0; i < 6; i ++ ) {

				renderTargetProperties.__webglFramebuffer[ i ] = _gl.createFramebuffer();

			}

		} else {

			renderTargetProperties.__webglFramebuffer = _gl.createFramebuffer();

		}

		// Setup color buffer

		if ( isCube ) {

			state.bindTexture( _gl.TEXTURE_CUBE_MAP, textureProperties.__webglTexture );
			setTextureParameters( _gl.TEXTURE_CUBE_MAP, renderTarget.texture, isTargetPowerOfTwo );

			for ( var i = 0; i < 6; i ++ ) {

				setupFrameBufferTexture( renderTargetProperties.__webglFramebuffer[ i ], renderTarget, _gl.COLOR_ATTACHMENT0, _gl.TEXTURE_CUBE_MAP_POSITIVE_X + i );

			}

			if ( renderTarget.texture.generateMipmaps && isTargetPowerOfTwo ) _gl.generateMipmap( _gl.TEXTURE_CUBE_MAP );
			state.bindTexture( _gl.TEXTURE_CUBE_MAP, null );

		} else {

			state.bindTexture( _gl.TEXTURE_2D, textureProperties.__webglTexture );
			setTextureParameters( _gl.TEXTURE_2D, renderTarget.texture, isTargetPowerOfTwo );
			setupFrameBufferTexture( renderTargetProperties.__webglFramebuffer, renderTarget, _gl.COLOR_ATTACHMENT0, _gl.TEXTURE_2D );

			if ( renderTarget.texture.generateMipmaps && isTargetPowerOfTwo ) _gl.generateMipmap( _gl.TEXTURE_2D );
			state.bindTexture( _gl.TEXTURE_2D, null );

		}

		// Setup depth and stencil buffers

		if ( renderTarget.depthBuffer ) {

			setupDepthRenderbuffer( renderTarget );

		}

	}

	function updateRenderTargetMipmap( renderTarget ) {

		var texture = renderTarget.texture;

		if ( texture.generateMipmaps && isPowerOfTwo( renderTarget ) &&
				texture.minFilter !== NearestFilter &&
				texture.minFilter !== LinearFilter ) {

			var target = (renderTarget && renderTarget.isWebGLRenderTargetCube) ? _gl.TEXTURE_CUBE_MAP : _gl.TEXTURE_2D;
			var webglTexture = properties.get( texture ).__webglTexture;

			state.bindTexture( target, webglTexture );
			_gl.generateMipmap( target );
			state.bindTexture( target, null );

		}

	}

	this.setTexture2D = setTexture2D;
	this.setTextureCube = setTextureCube;
	this.setTextureCubeDynamic = setTextureCubeDynamic;
	this.setupRenderTarget = setupRenderTarget;
	this.updateRenderTargetMipmap = updateRenderTargetMipmap;

}

/**
 * @author fordacious / fordacious.github.io
 */

function WebGLProperties() {

	var properties = {};

	return {

		get: function ( object ) {

			var uuid = object.uuid;
			var map = properties[ uuid ];

			if ( map === undefined ) {

				map = {};
				properties[ uuid ] = map;

			}

			return map;

		},

		delete: function ( object ) {

			delete properties[ object.uuid ];

		},

		clear: function () {

			properties = {};

		}

	};

}

/**
 * @author mrdoob / http://mrdoob.com/
 */

function WebGLState( gl, extensions, paramThreeToGL ) {

	function ColorBuffer() {

		var locked = false;

		var color = new Vector4();
		var currentColorMask = null;
		var currentColorClear = new Vector4();

		return {

			setMask: function ( colorMask ) {

				if ( currentColorMask !== colorMask && ! locked ) {

					gl.colorMask( colorMask, colorMask, colorMask, colorMask );
					currentColorMask = colorMask;

				}

			},

			setLocked: function ( lock ) {

				locked = lock;

			},

			setClear: function ( r, g, b, a, premultipliedAlpha ) {

				if ( premultipliedAlpha === true ) {

					r *= a; g *= a; b *= a;

				}

				color.set( r, g, b, a );

				if ( currentColorClear.equals( color ) === false ) {

					gl.clearColor( r, g, b, a );
					currentColorClear.copy( color );

				}

			},

			reset: function () {

				locked = false;

				currentColorMask = null;
				currentColorClear.set( 0, 0, 0, 1 );

			}

		};

	}

	function DepthBuffer() {

		var locked = false;

		var currentDepthMask = null;
		var currentDepthFunc = null;
		var currentDepthClear = null;

		return {

			setTest: function ( depthTest ) {

				if ( depthTest ) {

					enable( gl.DEPTH_TEST );

				} else {

					disable( gl.DEPTH_TEST );

				}

			},

			setMask: function ( depthMask ) {

				if ( currentDepthMask !== depthMask && ! locked ) {

					gl.depthMask( depthMask );
					currentDepthMask = depthMask;

				}

			},

			setFunc: function ( depthFunc ) {

				if ( currentDepthFunc !== depthFunc ) {

					if ( depthFunc ) {

						switch ( depthFunc ) {

							case NeverDepth:

								gl.depthFunc( gl.NEVER );
								break;

							case AlwaysDepth:

								gl.depthFunc( gl.ALWAYS );
								break;

							case LessDepth:

								gl.depthFunc( gl.LESS );
								break;

							case LessEqualDepth:

								gl.depthFunc( gl.LEQUAL );
								break;

							case EqualDepth:

								gl.depthFunc( gl.EQUAL );
								break;

							case GreaterEqualDepth:

								gl.depthFunc( gl.GEQUAL );
								break;

							case GreaterDepth:

								gl.depthFunc( gl.GREATER );
								break;

							case NotEqualDepth:

								gl.depthFunc( gl.NOTEQUAL );
								break;

							default:

								gl.depthFunc( gl.LEQUAL );

						}

					} else {

						gl.depthFunc( gl.LEQUAL );

					}

					currentDepthFunc = depthFunc;

				}

			},

			setLocked: function ( lock ) {

				locked = lock;

			},

			setClear: function ( depth ) {

				if ( currentDepthClear !== depth ) {

					gl.clearDepth( depth );
					currentDepthClear = depth;

				}

			},

			reset: function () {

				locked = false;

				currentDepthMask = null;
				currentDepthFunc = null;
				currentDepthClear = null;

			}

		};

	}

	function StencilBuffer() {

		var locked = false;

		var currentStencilMask = null;
		var currentStencilFunc = null;
		var currentStencilRef = null;
		var currentStencilFuncMask = null;
		var currentStencilFail = null;
		var currentStencilZFail = null;
		var currentStencilZPass = null;
		var currentStencilClear = null;

		return {

			setTest: function ( stencilTest ) {

				if ( stencilTest ) {

					enable( gl.STENCIL_TEST );

				} else {

					disable( gl.STENCIL_TEST );

				}

			},

			setMask: function ( stencilMask ) {

				if ( currentStencilMask !== stencilMask && ! locked ) {

					gl.stencilMask( stencilMask );
					currentStencilMask = stencilMask;

				}

			},

			setFunc: function ( stencilFunc, stencilRef, stencilMask ) {

				if ( currentStencilFunc !== stencilFunc ||
				     currentStencilRef 	!== stencilRef 	||
				     currentStencilFuncMask !== stencilMask ) {

					gl.stencilFunc( stencilFunc, stencilRef, stencilMask );

					currentStencilFunc = stencilFunc;
					currentStencilRef = stencilRef;
					currentStencilFuncMask = stencilMask;

				}

			},

			setOp: function ( stencilFail, stencilZFail, stencilZPass ) {

				if ( currentStencilFail	 !== stencilFail 	||
				     currentStencilZFail !== stencilZFail ||
				     currentStencilZPass !== stencilZPass ) {

					gl.stencilOp( stencilFail, stencilZFail, stencilZPass );

					currentStencilFail = stencilFail;
					currentStencilZFail = stencilZFail;
					currentStencilZPass = stencilZPass;

				}

			},

			setLocked: function ( lock ) {

				locked = lock;

			},

			setClear: function ( stencil ) {

				if ( currentStencilClear !== stencil ) {

					gl.clearStencil( stencil );
					currentStencilClear = stencil;

				}

			},

			reset: function () {

				locked = false;

				currentStencilMask = null;
				currentStencilFunc = null;
				currentStencilRef = null;
				currentStencilFuncMask = null;
				currentStencilFail = null;
				currentStencilZFail = null;
				currentStencilZPass = null;
				currentStencilClear = null;

			}

		};

	}

	//

	var colorBuffer = new ColorBuffer();
	var depthBuffer = new DepthBuffer();
	var stencilBuffer = new StencilBuffer();

	var maxVertexAttributes = gl.getParameter( gl.MAX_VERTEX_ATTRIBS );
	var newAttributes = new Uint8Array( maxVertexAttributes );
	var enabledAttributes = new Uint8Array( maxVertexAttributes );
	var attributeDivisors = new Uint8Array( maxVertexAttributes );

	var capabilities = {};

	var compressedTextureFormats = null;

	var currentBlending = null;
	var currentBlendEquation = null;
	var currentBlendSrc = null;
	var currentBlendDst = null;
	var currentBlendEquationAlpha = null;
	var currentBlendSrcAlpha = null;
	var currentBlendDstAlpha = null;
	var currentPremultipledAlpha = false;

	var currentFlipSided = null;
	var currentCullFace = null;

	var currentLineWidth = null;

	var currentPolygonOffsetFactor = null;
	var currentPolygonOffsetUnits = null;

	var currentScissorTest = null;

	var maxTextures = gl.getParameter( gl.MAX_TEXTURE_IMAGE_UNITS );

	var version = parseFloat( /^WebGL\ ([0-9])/.exec( gl.getParameter( gl.VERSION ) )[ 1 ] );
	var lineWidthAvailable = parseFloat( version ) >= 1.0;

	var currentTextureSlot = null;
	var currentBoundTextures = {};

	var currentScissor = new Vector4();
	var currentViewport = new Vector4();

	function createTexture( type, target, count ) {

		var data = new Uint8Array( 4 ); // 4 is required to match default unpack alignment of 4.
		var texture = gl.createTexture();

		gl.bindTexture( type, texture );
		gl.texParameteri( type, gl.TEXTURE_MIN_FILTER, gl.NEAREST );
		gl.texParameteri( type, gl.TEXTURE_MAG_FILTER, gl.NEAREST );

		for ( var i = 0; i < count; i ++ ) {

			gl.texImage2D( target + i, 0, gl.RGBA, 1, 1, 0, gl.RGBA, gl.UNSIGNED_BYTE, data );

		}

		return texture;

	}

	var emptyTextures = {};
	emptyTextures[ gl.TEXTURE_2D ] = createTexture( gl.TEXTURE_2D, gl.TEXTURE_2D, 1 );
	emptyTextures[ gl.TEXTURE_CUBE_MAP ] = createTexture( gl.TEXTURE_CUBE_MAP, gl.TEXTURE_CUBE_MAP_POSITIVE_X, 6 );

	//

	function init() {

		colorBuffer.setClear( 0, 0, 0, 1 );
		depthBuffer.setClear( 1 );
		stencilBuffer.setClear( 0 );

		enable( gl.DEPTH_TEST );
		setDepthFunc( LessEqualDepth );

		setFlipSided( false );
		setCullFace( CullFaceBack );
		enable( gl.CULL_FACE );

		enable( gl.BLEND );
		setBlending( NormalBlending );

	}

	function initAttributes() {

		for ( var i = 0, l = newAttributes.length; i < l; i ++ ) {

			newAttributes[ i ] = 0;

		}

	}

	function enableAttribute( attribute ) {

		newAttributes[ attribute ] = 1;

		if ( enabledAttributes[ attribute ] === 0 ) {

			gl.enableVertexAttribArray( attribute );
			enabledAttributes[ attribute ] = 1;

		}

		if ( attributeDivisors[ attribute ] !== 0 ) {

			var extension = extensions.get( 'ANGLE_instanced_arrays' );

			extension.vertexAttribDivisorANGLE( attribute, 0 );
			attributeDivisors[ attribute ] = 0;

		}

	}

	function enableAttributeAndDivisor( attribute, meshPerAttribute, extension ) {

		newAttributes[ attribute ] = 1;

		if ( enabledAttributes[ attribute ] === 0 ) {

			gl.enableVertexAttribArray( attribute );
			enabledAttributes[ attribute ] = 1;

		}

		if ( attributeDivisors[ attribute ] !== meshPerAttribute ) {

			extension.vertexAttribDivisorANGLE( attribute, meshPerAttribute );
			attributeDivisors[ attribute ] = meshPerAttribute;

		}

	}

	function disableUnusedAttributes() {

		for ( var i = 0, l = enabledAttributes.length; i !== l; ++ i ) {

			if ( enabledAttributes[ i ] !== newAttributes[ i ] ) {

				gl.disableVertexAttribArray( i );
				enabledAttributes[ i ] = 0;

			}

		}

	}

	function enable( id ) {

		if ( capabilities[ id ] !== true ) {

			gl.enable( id );
			capabilities[ id ] = true;

		}

	}

	function disable( id ) {

		if ( capabilities[ id ] !== false ) {

			gl.disable( id );
			capabilities[ id ] = false;

		}

	}

	function getCompressedTextureFormats() {

		if ( compressedTextureFormats === null ) {

			compressedTextureFormats = [];

			if ( extensions.get( 'WEBGL_compressed_texture_pvrtc' ) ||
			     extensions.get( 'WEBGL_compressed_texture_s3tc' ) ||
			     extensions.get( 'WEBGL_compressed_texture_etc1' ) ) {

				var formats = gl.getParameter( gl.COMPRESSED_TEXTURE_FORMATS );

				for ( var i = 0; i < formats.length; i ++ ) {

					compressedTextureFormats.push( formats[ i ] );

				}

			}

		}

		return compressedTextureFormats;

	}

	function setBlending( blending, blendEquation, blendSrc, blendDst, blendEquationAlpha, blendSrcAlpha, blendDstAlpha, premultipliedAlpha ) {

		if ( blending !== NoBlending ) {

			enable( gl.BLEND );

		} else {

			disable( gl.BLEND );

		}

		if ( blending !== currentBlending || premultipliedAlpha !== currentPremultipledAlpha ) {

			if ( blending === AdditiveBlending ) {

				if ( premultipliedAlpha ) {

					gl.blendEquationSeparate( gl.FUNC_ADD, gl.FUNC_ADD );
					gl.blendFuncSeparate( gl.ONE, gl.ONE, gl.ONE, gl.ONE );

				} else {

					gl.blendEquation( gl.FUNC_ADD );
					gl.blendFunc( gl.SRC_ALPHA, gl.ONE );

				}

			} else if ( blending === SubtractiveBlending ) {

				if ( premultipliedAlpha ) {

					gl.blendEquationSeparate( gl.FUNC_ADD, gl.FUNC_ADD );
					gl.blendFuncSeparate( gl.ZERO, gl.ZERO, gl.ONE_MINUS_SRC_COLOR, gl.ONE_MINUS_SRC_ALPHA );

				} else {

					gl.blendEquation( gl.FUNC_ADD );
					gl.blendFunc( gl.ZERO, gl.ONE_MINUS_SRC_COLOR );

				}

			} else if ( blending === MultiplyBlending ) {

				if ( premultipliedAlpha ) {

					gl.blendEquationSeparate( gl.FUNC_ADD, gl.FUNC_ADD );
					gl.blendFuncSeparate( gl.ZERO, gl.SRC_COLOR, gl.ZERO, gl.SRC_ALPHA );

				} else {

					gl.blendEquation( gl.FUNC_ADD );
					gl.blendFunc( gl.ZERO, gl.SRC_COLOR );

				}

			} else {

				if ( premultipliedAlpha ) {

					gl.blendEquationSeparate( gl.FUNC_ADD, gl.FUNC_ADD );
					gl.blendFuncSeparate( gl.ONE, gl.ONE_MINUS_SRC_ALPHA, gl.ONE, gl.ONE_MINUS_SRC_ALPHA );

				} else {

					gl.blendEquationSeparate( gl.FUNC_ADD, gl.FUNC_ADD );
					gl.blendFuncSeparate( gl.SRC_ALPHA, gl.ONE_MINUS_SRC_ALPHA, gl.ONE, gl.ONE_MINUS_SRC_ALPHA );

				}

			}

			currentBlending = blending;
			currentPremultipledAlpha = premultipliedAlpha;

		}

		if ( blending === CustomBlending ) {

			blendEquationAlpha = blendEquationAlpha || blendEquation;
			blendSrcAlpha = blendSrcAlpha || blendSrc;
			blendDstAlpha = blendDstAlpha || blendDst;

			if ( blendEquation !== currentBlendEquation || blendEquationAlpha !== currentBlendEquationAlpha ) {

				gl.blendEquationSeparate( paramThreeToGL( blendEquation ), paramThreeToGL( blendEquationAlpha ) );

				currentBlendEquation = blendEquation;
				currentBlendEquationAlpha = blendEquationAlpha;

			}

			if ( blendSrc !== currentBlendSrc || blendDst !== currentBlendDst || blendSrcAlpha !== currentBlendSrcAlpha || blendDstAlpha !== currentBlendDstAlpha ) {

				gl.blendFuncSeparate( paramThreeToGL( blendSrc ), paramThreeToGL( blendDst ), paramThreeToGL( blendSrcAlpha ), paramThreeToGL( blendDstAlpha ) );

				currentBlendSrc = blendSrc;
				currentBlendDst = blendDst;
				currentBlendSrcAlpha = blendSrcAlpha;
				currentBlendDstAlpha = blendDstAlpha;

			}

		} else {

			currentBlendEquation = null;
			currentBlendSrc = null;
			currentBlendDst = null;
			currentBlendEquationAlpha = null;
			currentBlendSrcAlpha = null;
			currentBlendDstAlpha = null;

		}

	}

	// TODO Deprecate

	function setColorWrite( colorWrite ) {

		colorBuffer.setMask( colorWrite );

	}

	function setDepthTest( depthTest ) {

		depthBuffer.setTest( depthTest );

	}

	function setDepthWrite( depthWrite ) {

		depthBuffer.setMask( depthWrite );

	}

	function setDepthFunc( depthFunc ) {

		depthBuffer.setFunc( depthFunc );

	}

	function setStencilTest( stencilTest ) {

		stencilBuffer.setTest( stencilTest );

	}

	function setStencilWrite( stencilWrite ) {

		stencilBuffer.setMask( stencilWrite );

	}

	function setStencilFunc( stencilFunc, stencilRef, stencilMask ) {

		stencilBuffer.setFunc( stencilFunc, stencilRef, stencilMask );

	}

	function setStencilOp( stencilFail, stencilZFail, stencilZPass ) {

		stencilBuffer.setOp( stencilFail, stencilZFail, stencilZPass );

	}

	//

	function setFlipSided( flipSided ) {

		if ( currentFlipSided !== flipSided ) {

			if ( flipSided ) {

				gl.frontFace( gl.CW );

			} else {

				gl.frontFace( gl.CCW );

			}

			currentFlipSided = flipSided;

		}

	}

	function setCullFace( cullFace ) {

		if ( cullFace !== CullFaceNone ) {

			enable( gl.CULL_FACE );

			if ( cullFace !== currentCullFace ) {

				if ( cullFace === CullFaceBack ) {

					gl.cullFace( gl.BACK );

				} else if ( cullFace === CullFaceFront ) {

					gl.cullFace( gl.FRONT );

				} else {

					gl.cullFace( gl.FRONT_AND_BACK );

				}

			}

		} else {

			disable( gl.CULL_FACE );

		}

		currentCullFace = cullFace;

	}

	function setLineWidth( width ) {

		if ( width !== currentLineWidth ) {

			if ( lineWidthAvailable ) gl.lineWidth( width );

			currentLineWidth = width;

		}

	}

	function setPolygonOffset( polygonOffset, factor, units ) {

		if ( polygonOffset ) {

			enable( gl.POLYGON_OFFSET_FILL );

			if ( currentPolygonOffsetFactor !== factor || currentPolygonOffsetUnits !== units ) {

				gl.polygonOffset( factor, units );

				currentPolygonOffsetFactor = factor;
				currentPolygonOffsetUnits = units;

			}

		} else {

			disable( gl.POLYGON_OFFSET_FILL );

		}

	}

	function getScissorTest() {

		return currentScissorTest;

	}

	function setScissorTest( scissorTest ) {

		currentScissorTest = scissorTest;

		if ( scissorTest ) {

			enable( gl.SCISSOR_TEST );

		} else {

			disable( gl.SCISSOR_TEST );

		}

	}

	// texture

	function activeTexture( webglSlot ) {

		if ( webglSlot === undefined ) webglSlot = gl.TEXTURE0 + maxTextures - 1;

		if ( currentTextureSlot !== webglSlot ) {

			gl.activeTexture( webglSlot );
			currentTextureSlot = webglSlot;

		}

	}

	function bindTexture( webglType, webglTexture ) {

		if ( currentTextureSlot === null ) {

			activeTexture();

		}

		var boundTexture = currentBoundTextures[ currentTextureSlot ];

		if ( boundTexture === undefined ) {

			boundTexture = { type: undefined, texture: undefined };
			currentBoundTextures[ currentTextureSlot ] = boundTexture;

		}

		if ( boundTexture.type !== webglType || boundTexture.texture !== webglTexture ) {

			gl.bindTexture( webglType, webglTexture || emptyTextures[ webglType ] );

			boundTexture.type = webglType;
			boundTexture.texture = webglTexture;

		}

	}

	function compressedTexImage2D() {

		try {

			gl.compressedTexImage2D.apply( gl, arguments );

		} catch ( error ) {

			console.error( error );

		}

	}

	function texImage2D() {

		try {

			gl.texImage2D.apply( gl, arguments );

		} catch ( error ) {

			console.error( error );

		}

	}

	//

	function scissor( scissor ) {

		if ( currentScissor.equals( scissor ) === false ) {

			gl.scissor( scissor.x, scissor.y, scissor.z, scissor.w );
			currentScissor.copy( scissor );

		}

	}

	function viewport( viewport ) {

		if ( currentViewport.equals( viewport ) === false ) {

			gl.viewport( viewport.x, viewport.y, viewport.z, viewport.w );
			currentViewport.copy( viewport );

		}

	}

	//

	function reset() {

		for ( var i = 0; i < enabledAttributes.length; i ++ ) {

			if ( enabledAttributes[ i ] === 1 ) {

				gl.disableVertexAttribArray( i );
				enabledAttributes[ i ] = 0;

			}

		}

		capabilities = {};

		compressedTextureFormats = null;

		currentTextureSlot = null;
		currentBoundTextures = {};

		currentBlending = null;

		currentFlipSided = null;
		currentCullFace = null;

		colorBuffer.reset();
		depthBuffer.reset();
		stencilBuffer.reset();

	}

	return {

		buffers: {
			color: colorBuffer,
			depth: depthBuffer,
			stencil: stencilBuffer
		},

		init: init,
		initAttributes: initAttributes,
		enableAttribute: enableAttribute,
		enableAttributeAndDivisor: enableAttributeAndDivisor,
		disableUnusedAttributes: disableUnusedAttributes,
		enable: enable,
		disable: disable,
		getCompressedTextureFormats: getCompressedTextureFormats,

		setBlending: setBlending,

		setColorWrite: setColorWrite,
		setDepthTest: setDepthTest,
		setDepthWrite: setDepthWrite,
		setDepthFunc: setDepthFunc,
		setStencilTest: setStencilTest,
		setStencilWrite: setStencilWrite,
		setStencilFunc: setStencilFunc,
		setStencilOp: setStencilOp,

		setFlipSided: setFlipSided,
		setCullFace: setCullFace,

		setLineWidth: setLineWidth,
		setPolygonOffset: setPolygonOffset,

		getScissorTest: getScissorTest,
		setScissorTest: setScissorTest,

		activeTexture: activeTexture,
		bindTexture: bindTexture,
		compressedTexImage2D: compressedTexImage2D,
		texImage2D: texImage2D,

		scissor: scissor,
		viewport: viewport,

		reset: reset

	};

}

/**
 * @author mrdoob / http://mrdoob.com/
 */

function WebGLCapabilities( gl, extensions, parameters ) {

	var maxAnisotropy;

	function getMaxAnisotropy() {

		if ( maxAnisotropy !== undefined ) return maxAnisotropy;

		var extension = extensions.get( 'EXT_texture_filter_anisotropic' );

		if ( extension !== null ) {

			maxAnisotropy = gl.getParameter( extension.MAX_TEXTURE_MAX_ANISOTROPY_EXT );

		} else {

			maxAnisotropy = 0;

		}

		return maxAnisotropy;

	}

	function getMaxPrecision( precision ) {

		if ( precision === 'highp' ) {

			if ( gl.getShaderPrecisionFormat( gl.VERTEX_SHADER, gl.HIGH_FLOAT ).precision > 0 &&
			     gl.getShaderPrecisionFormat( gl.FRAGMENT_SHADER, gl.HIGH_FLOAT ).precision > 0 ) {

				return 'highp';

			}

			precision = 'mediump';

		}

		if ( precision === 'mediump' ) {

			if ( gl.getShaderPrecisionFormat( gl.VERTEX_SHADER, gl.MEDIUM_FLOAT ).precision > 0 &&
			     gl.getShaderPrecisionFormat( gl.FRAGMENT_SHADER, gl.MEDIUM_FLOAT ).precision > 0 ) {

				return 'mediump';

			}

		}

		return 'lowp';

	}

	var precision = parameters.precision !== undefined ? parameters.precision : 'highp';
	var maxPrecision = getMaxPrecision( precision );

	if ( maxPrecision !== precision ) {

		console.warn( 'THREE.WebGLRenderer:', precision, 'not supported, using', maxPrecision, 'instead.' );
		precision = maxPrecision;

	}

	var logarithmicDepthBuffer = parameters.logarithmicDepthBuffer === true && !! extensions.get( 'EXT_frag_depth' );

	var maxTextures = gl.getParameter( gl.MAX_TEXTURE_IMAGE_UNITS );
	var maxVertexTextures = gl.getParameter( gl.MAX_VERTEX_TEXTURE_IMAGE_UNITS );
	var maxTextureSize = gl.getParameter( gl.MAX_TEXTURE_SIZE );
	var maxCubemapSize = gl.getParameter( gl.MAX_CUBE_MAP_TEXTURE_SIZE );

	var maxAttributes = gl.getParameter( gl.MAX_VERTEX_ATTRIBS );
	var maxVertexUniforms = gl.getParameter( gl.MAX_VERTEX_UNIFORM_VECTORS );
	var maxVaryings = gl.getParameter( gl.MAX_VARYING_VECTORS );
	var maxFragmentUniforms = gl.getParameter( gl.MAX_FRAGMENT_UNIFORM_VECTORS );

	var vertexTextures = maxVertexTextures > 0;
	var floatFragmentTextures = !! extensions.get( 'OES_texture_float' );
	var floatVertexTextures = vertexTextures && floatFragmentTextures;

	return {

		getMaxAnisotropy: getMaxAnisotropy,
		getMaxPrecision: getMaxPrecision,

		precision: precision,
		logarithmicDepthBuffer: logarithmicDepthBuffer,

		maxTextures: maxTextures,
		maxVertexTextures: maxVertexTextures,
		maxTextureSize: maxTextureSize,
		maxCubemapSize: maxCubemapSize,

		maxAttributes: maxAttributes,
		maxVertexUniforms: maxVertexUniforms,
		maxVaryings: maxVaryings,
		maxFragmentUniforms: maxFragmentUniforms,

		vertexTextures: vertexTextures,
		floatFragmentTextures: floatFragmentTextures,
		floatVertexTextures: floatVertexTextures

	};

}

/**
 * @author mrdoob / http://mrdoob.com/
 */

function WebGLExtensions( gl ) {

	var extensions = {};

	return {

		get: function ( name ) {

			if ( extensions[ name ] !== undefined ) {

				return extensions[ name ];

			}

			var extension;

			switch ( name ) {

				case 'WEBGL_depth_texture':
					extension = gl.getExtension( 'WEBGL_depth_texture' ) || gl.getExtension( 'MOZ_WEBGL_depth_texture' ) || gl.getExtension( 'WEBKIT_WEBGL_depth_texture' );
					break;

				case 'EXT_texture_filter_anisotropic':
					extension = gl.getExtension( 'EXT_texture_filter_anisotropic' ) || gl.getExtension( 'MOZ_EXT_texture_filter_anisotropic' ) || gl.getExtension( 'WEBKIT_EXT_texture_filter_anisotropic' );
					break;

				case 'WEBGL_compressed_texture_s3tc':
					extension = gl.getExtension( 'WEBGL_compressed_texture_s3tc' ) || gl.getExtension( 'MOZ_WEBGL_compressed_texture_s3tc' ) || gl.getExtension( 'WEBKIT_WEBGL_compressed_texture_s3tc' );
					break;

				case 'WEBGL_compressed_texture_pvrtc':
					extension = gl.getExtension( 'WEBGL_compressed_texture_pvrtc' ) || gl.getExtension( 'WEBKIT_WEBGL_compressed_texture_pvrtc' );
					break;

				case 'WEBGL_compressed_texture_etc1':
					extension = gl.getExtension( 'WEBGL_compressed_texture_etc1' );
					break;

				default:
					extension = gl.getExtension( name );

			}

			if ( extension === null ) {

				console.warn( 'THREE.WebGLRenderer: ' + name + ' extension not supported.' );

			}

			extensions[ name ] = extension;

			return extension;

		}

	};

}

/**
 * @author tschw
 */

function WebGLClipping() {

	var scope = this,

		globalState = null,
		numGlobalPlanes = 0,
		localClippingEnabled = false,
		renderingShadows = false,

		plane = new Plane(),
		viewNormalMatrix = new Matrix3(),

		uniform = { value: null, needsUpdate: false };

	this.uniform = uniform;
	this.numPlanes = 0;
	this.numIntersection = 0;

	this.init = function( planes, enableLocalClipping, camera ) {

		var enabled =
			planes.length !== 0 ||
			enableLocalClipping ||
			// enable state of previous frame - the clipping code has to
			// run another frame in order to reset the state:
			numGlobalPlanes !== 0 ||
			localClippingEnabled;

		localClippingEnabled = enableLocalClipping;

		globalState = projectPlanes( planes, camera, 0 );
		numGlobalPlanes = planes.length;

		return enabled;

	};

	this.beginShadows = function() {

		renderingShadows = true;
		projectPlanes( null );

	};

	this.endShadows = function() {

		renderingShadows = false;
		resetGlobalState();

	};

	this.setState = function( planes, clipIntersection, clipShadows, camera, cache, fromCache ) {

		if ( ! localClippingEnabled ||
				planes === null || planes.length === 0 ||
				renderingShadows && ! clipShadows ) {
			// there's no local clipping

			if ( renderingShadows ) {
				// there's no global clipping

				projectPlanes( null );

			} else {

				resetGlobalState();
			}

		} else {

			var nGlobal = renderingShadows ? 0 : numGlobalPlanes,
				lGlobal = nGlobal * 4,

				dstArray = cache.clippingState || null;

			uniform.value = dstArray; // ensure unique state

			dstArray = projectPlanes( planes, camera, lGlobal, fromCache );

			for ( var i = 0; i !== lGlobal; ++ i ) {

				dstArray[ i ] = globalState[ i ];

			}

			cache.clippingState = dstArray;
			this.numIntersection = clipIntersection ? this.numPlanes : 0;
			this.numPlanes += nGlobal;

		}


	};

	function resetGlobalState() {

		if ( uniform.value !== globalState ) {

			uniform.value = globalState;
			uniform.needsUpdate = numGlobalPlanes > 0;

		}

		scope.numPlanes = numGlobalPlanes;
		scope.numIntersection = 0;

	}

	function projectPlanes( planes, camera, dstOffset, skipTransform ) {

		var nPlanes = planes !== null ? planes.length : 0,
			dstArray = null;

		if ( nPlanes !== 0 ) {

			dstArray = uniform.value;

			if ( skipTransform !== true || dstArray === null ) {

				var flatSize = dstOffset + nPlanes * 4,
					viewMatrix = camera.matrixWorldInverse;

				viewNormalMatrix.getNormalMatrix( viewMatrix );

				if ( dstArray === null || dstArray.length < flatSize ) {

					dstArray = new Float32Array( flatSize );

				}

				for ( var i = 0, i4 = dstOffset;
									i !== nPlanes; ++ i, i4 += 4 ) {

					plane.copy( planes[ i ] ).
							applyMatrix4( viewMatrix, viewNormalMatrix );

					plane.normal.toArray( dstArray, i4 );
					dstArray[ i4 + 3 ] = plane.constant;

				}

			}

			uniform.value = dstArray;
			uniform.needsUpdate = true;

		}

		scope.numPlanes = nPlanes;
		
		return dstArray;

	}

}

/**
 * @author supereggbert / http://www.paulbrunt.co.uk/
 * @author mrdoob / http://mrdoob.com/
 * @author alteredq / http://alteredqualia.com/
 * @author szimek / https://github.com/szimek/
 * @author tschw
 */

function WebGLRenderer( parameters ) {

	console.log( 'THREE.WebGLRenderer', REVISION );

	parameters = parameters || {};

	var _canvas = parameters.canvas !== undefined ? parameters.canvas : document.createElementNS( 'http://www.w3.org/1999/xhtml', 'canvas' ),
		_context = parameters.context !== undefined ? parameters.context : null,

		_alpha = parameters.alpha !== undefined ? parameters.alpha : false,
		_depth = parameters.depth !== undefined ? parameters.depth : true,
		_stencil = parameters.stencil !== undefined ? parameters.stencil : true,
		_antialias = parameters.antialias !== undefined ? parameters.antialias : false,
		_premultipliedAlpha = parameters.premultipliedAlpha !== undefined ? parameters.premultipliedAlpha : true,
		_preserveDrawingBuffer = parameters.preserveDrawingBuffer !== undefined ? parameters.preserveDrawingBuffer : false;

	var lights = [];

	var opaqueObjects = [];
	var opaqueObjectsLastIndex = - 1;
	var transparentObjects = [];
	var transparentObjectsLastIndex = - 1;

	var morphInfluences = new Float32Array( 8 );

	var sprites = [];
	var lensFlares = [];

	// public properties

	this.domElement = _canvas;
	this.context = null;

	// clearing

	this.autoClear = true;
	this.autoClearColor = true;
	this.autoClearDepth = true;
	this.autoClearStencil = true;

	// scene graph

	this.sortObjects = true;

	// user-defined clipping

	this.clippingPlanes = [];
	this.localClippingEnabled = false;

	// physically based shading

	this.gammaFactor = 2.0;	// for backwards compatibility
	this.gammaInput = false;
	this.gammaOutput = false;

	// physical lights

	this.physicallyCorrectLights = false;

	// tone mapping

	this.toneMapping = LinearToneMapping;
	this.toneMappingExposure = 1.0;
	this.toneMappingWhitePoint = 1.0;

	// morphs

	this.maxMorphTargets = 8;
	this.maxMorphNormals = 4;

	// internal properties

	var _this = this,

		// internal state cache

		_currentProgram = null,
		_currentRenderTarget = null,
		_currentFramebuffer = null,
		_currentMaterialId = - 1,
		_currentGeometryProgram = '',
		_currentCamera = null,

		_currentScissor = new Vector4(),
		_currentScissorTest = null,

		_currentViewport = new Vector4(),

		//

		_usedTextureUnits = 0,

		//

		_clearColor = new Color( 0x000000 ),
		_clearAlpha = 0,

		_width = _canvas.width,
		_height = _canvas.height,

		_pixelRatio = 1,

		_scissor = new Vector4( 0, 0, _width, _height ),
		_scissorTest = false,

		_viewport = new Vector4( 0, 0, _width, _height ),

		// frustum

		_frustum = new Frustum(),

		// clipping

		_clipping = new WebGLClipping(),
		_clippingEnabled = false,
		_localClippingEnabled = false,

		_sphere = new Sphere(),

		// camera matrices cache

		_projScreenMatrix = new Matrix4(),

		_vector3 = new Vector3(),
		_matrix4 = new Matrix4(),
		_matrix42 = new Matrix4(),

		// light arrays cache

		_lights = {

			hash: '',

		ambient: [ 0, 0, 0 ],
		directional: [],
		directionalShadowMap: [],
		directionalShadowMatrix: [],
		spot: [],
		spotShadowMap: [],
		spotShadowMatrix: [],
		rectArea: [],
		point: [],
		pointShadowMap: [],
		pointShadowMatrix: [],
		hemi: [],

			shadows: []

		},

		// info

		_infoRender = {

			calls: 0,
			vertices: 0,
			faces: 0,
			points: 0

		};

	this.info = {

		render: _infoRender,
		memory: {

			geometries: 0,
			textures: 0

		},
		programs: null

	};


	// initialize

	var _gl;

	try {

		var attributes = {
			alpha: _alpha,
			depth: _depth,
			stencil: _stencil,
			antialias: _antialias,
			premultipliedAlpha: _premultipliedAlpha,
			preserveDrawingBuffer: _preserveDrawingBuffer
		};

		_gl = _context || _canvas.getContext( 'webgl', attributes ) || _canvas.getContext( 'experimental-webgl', attributes );

		if ( _gl === null ) {

			if ( _canvas.getContext( 'webgl' ) !== null ) {

				throw 'Error creating WebGL context with your selected attributes.';

			} else {

				throw 'Error creating WebGL context.';

			}

		}

		// Some experimental-webgl implementations do not have getShaderPrecisionFormat

		if ( _gl.getShaderPrecisionFormat === undefined ) {

			_gl.getShaderPrecisionFormat = function () {

				return { 'rangeMin': 1, 'rangeMax': 1, 'precision': 1 };

			};

		}

		_canvas.addEventListener( 'webglcontextlost', onContextLost, false );

	} catch ( error ) {

		console.error( 'THREE.WebGLRenderer: ' + error );

	}

	var extensions = new WebGLExtensions( _gl );

	extensions.get( 'WEBGL_depth_texture' );
	extensions.get( 'OES_texture_float' );
	extensions.get( 'OES_texture_float_linear' );
	extensions.get( 'OES_texture_half_float' );
	extensions.get( 'OES_texture_half_float_linear' );
	extensions.get( 'OES_standard_derivatives' );
	extensions.get( 'ANGLE_instanced_arrays' );

	if ( extensions.get( 'OES_element_index_uint' ) ) {

		BufferGeometry.MaxIndex = 4294967296;

	}

	var capabilities = new WebGLCapabilities( _gl, extensions, parameters );

	var state = new WebGLState( _gl, extensions, paramThreeToGL );
	var properties = new WebGLProperties();
	var textures = new WebGLTextures( _gl, extensions, state, properties, capabilities, paramThreeToGL, this.info );
	var objects = new WebGLObjects( _gl, properties, this.info );
	var programCache = new WebGLPrograms( this, capabilities );
	var lightCache = new WebGLLights();

	this.info.programs = programCache.programs;

	var bufferRenderer = new WebGLBufferRenderer( _gl, extensions, _infoRender );
	var indexedBufferRenderer = new WebGLIndexedBufferRenderer( _gl, extensions, _infoRender );

	//

	var backgroundPlaneCamera, backgroundPlaneMesh;
	var backgroundBoxCamera, backgroundBoxMesh;

	//

	function getTargetPixelRatio() {

		return _currentRenderTarget === null ? _pixelRatio : 1;

	}

	function setDefaultGLState() {

		state.init();

		state.scissor( _currentScissor.copy( _scissor ).multiplyScalar( _pixelRatio ) );
		state.viewport( _currentViewport.copy( _viewport ).multiplyScalar( _pixelRatio ) );

		state.buffers.color.setClear( _clearColor.r, _clearColor.g, _clearColor.b, _clearAlpha, _premultipliedAlpha );

	}

	function resetGLState() {

		_currentProgram = null;
		_currentCamera = null;

		_currentGeometryProgram = '';
		_currentMaterialId = - 1;

		state.reset();

	}

	setDefaultGLState();

	this.context = _gl;
	this.capabilities = capabilities;
	this.extensions = extensions;
	this.properties = properties;
	this.state = state;

	// shadow map

	var shadowMap = new WebGLShadowMap( this, _lights, objects, capabilities );

	this.shadowMap = shadowMap;


	// Plugins

	var spritePlugin = new SpritePlugin( this, sprites );
	var lensFlarePlugin = new LensFlarePlugin( this, lensFlares );

	// API

	this.getContext = function () {

		return _gl;

	};

	this.getContextAttributes = function () {

		return _gl.getContextAttributes();

	};

	this.forceContextLoss = function () {

		extensions.get( 'WEBGL_lose_context' ).loseContext();

	};

	this.getMaxAnisotropy = function () {

		return capabilities.getMaxAnisotropy();

	};

	this.getPrecision = function () {

		return capabilities.precision;

	};

	this.getPixelRatio = function () {

		return _pixelRatio;

	};

	this.setPixelRatio = function ( value ) {

		if ( value === undefined ) return;

		_pixelRatio = value;

		this.setSize( _viewport.z, _viewport.w, false );

	};

	this.getSize = function () {

		return {
			width: _width,
			height: _height
		};

	};

	this.setSize = function ( width, height, updateStyle ) {

		_width = width;
		_height = height;

		_canvas.width = width * _pixelRatio;
		_canvas.height = height * _pixelRatio;

		if ( updateStyle !== false ) {

			_canvas.style.width = width + 'px';
			_canvas.style.height = height + 'px';

		}

		this.setViewport( 0, 0, width, height );

	};

	this.setViewport = function ( x, y, width, height ) {

		state.viewport( _viewport.set( x, y, width, height ) );

	};

	this.setScissor = function ( x, y, width, height ) {

		state.scissor( _scissor.set( x, y, width, height ) );

	};

	this.setScissorTest = function ( boolean ) {

		state.setScissorTest( _scissorTest = boolean );

	};

	// Clearing

	this.getClearColor = function () {

		return _clearColor;

	};

	this.setClearColor = function ( color, alpha ) {

		_clearColor.set( color );

		_clearAlpha = alpha !== undefined ? alpha : 1;

		state.buffers.color.setClear( _clearColor.r, _clearColor.g, _clearColor.b, _clearAlpha, _premultipliedAlpha );

	};

	this.getClearAlpha = function () {

		return _clearAlpha;

	};

	this.setClearAlpha = function ( alpha ) {

		_clearAlpha = alpha;

		state.buffers.color.setClear( _clearColor.r, _clearColor.g, _clearColor.b, _clearAlpha, _premultipliedAlpha );

	};

	this.clear = function ( color, depth, stencil ) {

		var bits = 0;

		if ( color === undefined || color ) bits |= _gl.COLOR_BUFFER_BIT;
		if ( depth === undefined || depth ) bits |= _gl.DEPTH_BUFFER_BIT;
		if ( stencil === undefined || stencil ) bits |= _gl.STENCIL_BUFFER_BIT;

		_gl.clear( bits );

	};

	this.clearColor = function () {

		this.clear( true, false, false );

	};

	this.clearDepth = function () {

		this.clear( false, true, false );

	};

	this.clearStencil = function () {

		this.clear( false, false, true );

	};

	this.clearTarget = function ( renderTarget, color, depth, stencil ) {

		this.setRenderTarget( renderTarget );
		this.clear( color, depth, stencil );

	};

	// Reset

	this.resetGLState = resetGLState;

	this.dispose = function() {

		transparentObjects = [];
		transparentObjectsLastIndex = -1;
		opaqueObjects = [];
		opaqueObjectsLastIndex = -1;

		_canvas.removeEventListener( 'webglcontextlost', onContextLost, false );

	};

	// Events

	function onContextLost( event ) {

		event.preventDefault();

		resetGLState();
		setDefaultGLState();

		properties.clear();

	}

	function onMaterialDispose( event ) {

		var material = event.target;

		material.removeEventListener( 'dispose', onMaterialDispose );

		deallocateMaterial( material );

	}

	// Buffer deallocation

	function deallocateMaterial( material ) {

		releaseMaterialProgramReference( material );

		properties.delete( material );

	}


	function releaseMaterialProgramReference( material ) {

		var programInfo = properties.get( material ).program;

		material.program = undefined;

		if ( programInfo !== undefined ) {

			programCache.releaseProgram( programInfo );

		}

	}

	// Buffer rendering

	this.renderBufferImmediate = function ( object, program, material ) {

		state.initAttributes();

		var buffers = properties.get( object );

		if ( object.hasPositions && ! buffers.position ) buffers.position = _gl.createBuffer();
		if ( object.hasNormals && ! buffers.normal ) buffers.normal = _gl.createBuffer();
		if ( object.hasUvs && ! buffers.uv ) buffers.uv = _gl.createBuffer();
		if ( object.hasColors && ! buffers.color ) buffers.color = _gl.createBuffer();

		var attributes = program.getAttributes();

		if ( object.hasPositions ) {

			_gl.bindBuffer( _gl.ARRAY_BUFFER, buffers.position );
			_gl.bufferData( _gl.ARRAY_BUFFER, object.positionArray, _gl.DYNAMIC_DRAW );

			state.enableAttribute( attributes.position );
			_gl.vertexAttribPointer( attributes.position, 3, _gl.FLOAT, false, 0, 0 );

		}

		if ( object.hasNormals ) {

			_gl.bindBuffer( _gl.ARRAY_BUFFER, buffers.normal );

			if ( ! material.isMeshPhongMaterial &&
				! material.isMeshStandardMaterial &&
				! material.isMeshNormalMaterial &&
				material.shading === FlatShading ) {

				for ( var i = 0, l = object.count * 3; i < l; i += 9 ) {

					var array = object.normalArray;

					var nx = ( array[ i + 0 ] + array[ i + 3 ] + array[ i + 6 ] ) / 3;
					var ny = ( array[ i + 1 ] + array[ i + 4 ] + array[ i + 7 ] ) / 3;
					var nz = ( array[ i + 2 ] + array[ i + 5 ] + array[ i + 8 ] ) / 3;

					array[ i + 0 ] = nx;
					array[ i + 1 ] = ny;
					array[ i + 2 ] = nz;

					array[ i + 3 ] = nx;
					array[ i + 4 ] = ny;
					array[ i + 5 ] = nz;

					array[ i + 6 ] = nx;
					array[ i + 7 ] = ny;
					array[ i + 8 ] = nz;

				}

			}

			_gl.bufferData( _gl.ARRAY_BUFFER, object.normalArray, _gl.DYNAMIC_DRAW );

			state.enableAttribute( attributes.normal );

			_gl.vertexAttribPointer( attributes.normal, 3, _gl.FLOAT, false, 0, 0 );

		}

		if ( object.hasUvs && material.map ) {

			_gl.bindBuffer( _gl.ARRAY_BUFFER, buffers.uv );
			_gl.bufferData( _gl.ARRAY_BUFFER, object.uvArray, _gl.DYNAMIC_DRAW );

			state.enableAttribute( attributes.uv );

			_gl.vertexAttribPointer( attributes.uv, 2, _gl.FLOAT, false, 0, 0 );

		}

		if ( object.hasColors && material.vertexColors !== NoColors ) {

			_gl.bindBuffer( _gl.ARRAY_BUFFER, buffers.color );
			_gl.bufferData( _gl.ARRAY_BUFFER, object.colorArray, _gl.DYNAMIC_DRAW );

			state.enableAttribute( attributes.color );

			_gl.vertexAttribPointer( attributes.color, 3, _gl.FLOAT, false, 0, 0 );

		}

		state.disableUnusedAttributes();

		_gl.drawArrays( _gl.TRIANGLES, 0, object.count );

		object.count = 0;

	};

	this.renderBufferDirect = function ( camera, fog, geometry, material, object, group ) {

		setMaterial( material );

		var program = setProgram( camera, fog, material, object );

		var updateBuffers = false;
		var geometryProgram = geometry.id + '_' + program.id + '_' + material.wireframe;

		if ( geometryProgram !== _currentGeometryProgram ) {

			_currentGeometryProgram = geometryProgram;
			updateBuffers = true;

		}

		// morph targets

		var morphTargetInfluences = object.morphTargetInfluences;

		if ( morphTargetInfluences !== undefined ) {

			var activeInfluences = [];

			for ( var i = 0, l = morphTargetInfluences.length; i < l; i ++ ) {

				var influence = morphTargetInfluences[ i ];
				activeInfluences.push( [ influence, i ] );

			}

			activeInfluences.sort( absNumericalSort );

			if ( activeInfluences.length > 8 ) {

				activeInfluences.length = 8;

			}

			var morphAttributes = geometry.morphAttributes;

			for ( var i = 0, l = activeInfluences.length; i < l; i ++ ) {

				var influence = activeInfluences[ i ];
				morphInfluences[ i ] = influence[ 0 ];

				if ( influence[ 0 ] !== 0 ) {

					var index = influence[ 1 ];

					if ( material.morphTargets === true && morphAttributes.position ) geometry.addAttribute( 'morphTarget' + i, morphAttributes.position[ index ] );
					if ( material.morphNormals === true && morphAttributes.normal ) geometry.addAttribute( 'morphNormal' + i, morphAttributes.normal[ index ] );

				} else {

					if ( material.morphTargets === true ) geometry.removeAttribute( 'morphTarget' + i );
					if ( material.morphNormals === true ) geometry.removeAttribute( 'morphNormal' + i );

				}

			}

			for ( var i = activeInfluences.length, il = morphInfluences.length; i < il; i ++ ) {

				morphInfluences[ i ] = 0.0;

			}

			program.getUniforms().setValue(
				_gl, 'morphTargetInfluences', morphInfluences );

			updateBuffers = true;

		}

		//

		var index = geometry.index;
		var position = geometry.attributes.position;
		var rangeFactor = 1;

		if ( material.wireframe === true ) {

			index = objects.getWireframeAttribute( geometry );
			rangeFactor = 2;

		}

		var renderer;

		if ( index !== null ) {

			renderer = indexedBufferRenderer;
			renderer.setIndex( index );

		} else {

			renderer = bufferRenderer;

		}

		if ( updateBuffers ) {

			setupVertexAttributes( material, program, geometry );

			if ( index !== null ) {

				_gl.bindBuffer( _gl.ELEMENT_ARRAY_BUFFER, objects.getAttributeBuffer( index ) );

			}

		}

		//

		var dataCount = 0;

		if ( index !== null ) {

			dataCount = index.count;

		} else if ( position !== undefined ) {

			dataCount = position.count;

		}

		var rangeStart = geometry.drawRange.start * rangeFactor;
		var rangeCount = geometry.drawRange.count * rangeFactor;

		var groupStart = group !== null ? group.start * rangeFactor : 0;
		var groupCount = group !== null ? group.count * rangeFactor : Infinity;

		var drawStart = Math.max( rangeStart, groupStart );
		var drawEnd = Math.min( dataCount, rangeStart + rangeCount, groupStart + groupCount ) - 1;

		var drawCount = Math.max( 0, drawEnd - drawStart + 1 );

		if ( drawCount === 0 ) return;

		//

		if ( object.isMesh ) {

			if ( material.wireframe === true ) {

				state.setLineWidth( material.wireframeLinewidth * getTargetPixelRatio() );
				renderer.setMode( _gl.LINES );

			} else {

				switch ( object.drawMode ) {

					case TrianglesDrawMode:
						renderer.setMode( _gl.TRIANGLES );
						break;

					case TriangleStripDrawMode:
						renderer.setMode( _gl.TRIANGLE_STRIP );
						break;

					case TriangleFanDrawMode:
						renderer.setMode( _gl.TRIANGLE_FAN );
						break;

				}

			}


		} else if ( object.isLine ) {

			var lineWidth = material.linewidth;

			if ( lineWidth === undefined ) lineWidth = 1; // Not using Line*Material

			state.setLineWidth( lineWidth * getTargetPixelRatio() );

			if ( object.isLineSegments ) {

				renderer.setMode( _gl.LINES );

			} else {

				renderer.setMode( _gl.LINE_STRIP );

			}

		} else if ( object.isPoints ) {

			renderer.setMode( _gl.POINTS );

		}

		if ( geometry && geometry.isInstancedBufferGeometry ) {

			if ( geometry.maxInstancedCount > 0 ) {

				renderer.renderInstances( geometry, drawStart, drawCount );

			}

		} else {

			renderer.render( drawStart, drawCount );

		}

	};

	function setupVertexAttributes( material, program, geometry, startIndex ) {

		var extension;

		if ( geometry && geometry.isInstancedBufferGeometry ) {

			extension = extensions.get( 'ANGLE_instanced_arrays' );

			if ( extension === null ) {

				console.error( 'THREE.WebGLRenderer.setupVertexAttributes: using THREE.InstancedBufferGeometry but hardware does not support extension ANGLE_instanced_arrays.' );
				return;

			}

		}

		if ( startIndex === undefined ) startIndex = 0;

		state.initAttributes();

		var geometryAttributes = geometry.attributes;

		var programAttributes = program.getAttributes();

		var materialDefaultAttributeValues = material.defaultAttributeValues;

		for ( var name in programAttributes ) {

			var programAttribute = programAttributes[ name ];

			if ( programAttribute >= 0 ) {

				var geometryAttribute = geometryAttributes[ name ];

				if ( geometryAttribute !== undefined ) {

					var normalized = geometryAttribute.normalized;
					var size = geometryAttribute.itemSize;

					var attributeProperties = objects.getAttributeProperties( geometryAttribute );

					var buffer = attributeProperties.__webglBuffer;
					var type = attributeProperties.type;
					var bytesPerElement = attributeProperties.bytesPerElement;

					if ( geometryAttribute.isInterleavedBufferAttribute ) {

						var data = geometryAttribute.data;
						var stride = data.stride;
						var offset = geometryAttribute.offset;

						if ( data && data.isInstancedInterleavedBuffer ) {

							state.enableAttributeAndDivisor( programAttribute, data.meshPerAttribute, extension );

							if ( geometry.maxInstancedCount === undefined ) {

								geometry.maxInstancedCount = data.meshPerAttribute * data.count;

							}

						} else {

							state.enableAttribute( programAttribute );

						}

						_gl.bindBuffer( _gl.ARRAY_BUFFER, buffer );
						_gl.vertexAttribPointer( programAttribute, size, type, normalized, stride * bytesPerElement, ( startIndex * stride + offset ) * bytesPerElement );

					} else {

						if ( geometryAttribute.isInstancedBufferAttribute ) {

							state.enableAttributeAndDivisor( programAttribute, geometryAttribute.meshPerAttribute, extension );

							if ( geometry.maxInstancedCount === undefined ) {

								geometry.maxInstancedCount = geometryAttribute.meshPerAttribute * geometryAttribute.count;

							}

						} else {

							state.enableAttribute( programAttribute );

						}

						_gl.bindBuffer( _gl.ARRAY_BUFFER, buffer );
						_gl.vertexAttribPointer( programAttribute, size, type, normalized, 0, startIndex * size * bytesPerElement );

					}

				} else if ( materialDefaultAttributeValues !== undefined ) {

					var value = materialDefaultAttributeValues[ name ];

					if ( value !== undefined ) {

						switch ( value.length ) {

							case 2:
								_gl.vertexAttrib2fv( programAttribute, value );
								break;

							case 3:
								_gl.vertexAttrib3fv( programAttribute, value );
								break;

							case 4:
								_gl.vertexAttrib4fv( programAttribute, value );
								break;

							default:
								_gl.vertexAttrib1fv( programAttribute, value );

						}

					}

				}

			}

		}

		state.disableUnusedAttributes();

	}

	// Sorting

	function absNumericalSort( a, b ) {

		return Math.abs( b[ 0 ] ) - Math.abs( a[ 0 ] );

	}

	function painterSortStable( a, b ) {

		if ( a.object.renderOrder !== b.object.renderOrder ) {

			return a.object.renderOrder - b.object.renderOrder;

		} else if ( a.material.program && b.material.program && a.material.program !== b.material.program ) {

			return a.material.program.id - b.material.program.id;

		} else if ( a.material.id !== b.material.id ) {

			return a.material.id - b.material.id;

		} else if ( a.z !== b.z ) {

			return a.z - b.z;

		} else {

			return a.id - b.id;

		}

	}

	function reversePainterSortStable( a, b ) {

		if ( a.object.renderOrder !== b.object.renderOrder ) {

			return a.object.renderOrder - b.object.renderOrder;

		} if ( a.z !== b.z ) {

			return b.z - a.z;

		} else {

			return a.id - b.id;

		}

	}

	// Rendering

	this.render = function ( scene, camera, renderTarget, forceClear ) {

		if ( camera !== undefined && camera.isCamera !== true ) {

			console.error( 'THREE.WebGLRenderer.render: camera is not an instance of THREE.Camera.' );
			return;

		}

		// reset caching for this frame

		_currentGeometryProgram = '';
		_currentMaterialId = - 1;
		_currentCamera = null;

		// update scene graph

		if ( scene.autoUpdate === true ) scene.updateMatrixWorld();

		// update camera matrices and frustum

		if ( camera.parent === null ) camera.updateMatrixWorld();

		camera.matrixWorldInverse.getInverse( camera.matrixWorld );

		_projScreenMatrix.multiplyMatrices( camera.projectionMatrix, camera.matrixWorldInverse );
		_frustum.setFromMatrix( _projScreenMatrix );

		lights.length = 0;

		opaqueObjectsLastIndex = - 1;
		transparentObjectsLastIndex = - 1;

		sprites.length = 0;
		lensFlares.length = 0;

		_localClippingEnabled = this.localClippingEnabled;
		_clippingEnabled = _clipping.init( this.clippingPlanes, _localClippingEnabled, camera );

		projectObject( scene, camera );

		opaqueObjects.length = opaqueObjectsLastIndex + 1;
		transparentObjects.length = transparentObjectsLastIndex + 1;

		if ( _this.sortObjects === true ) {

			opaqueObjects.sort( painterSortStable );
			transparentObjects.sort( reversePainterSortStable );

		}

		//

		if ( _clippingEnabled ) _clipping.beginShadows();

		setupShadows( lights );

		shadowMap.render( scene, camera );

		setupLights( lights, camera );

		if ( _clippingEnabled ) _clipping.endShadows();

		//

		_infoRender.calls = 0;
		_infoRender.vertices = 0;
		_infoRender.faces = 0;
		_infoRender.points = 0;

		if ( renderTarget === undefined ) {

			renderTarget = null;

		}

		this.setRenderTarget( renderTarget );

		//

		var background = scene.background;

		if ( background === null ) {

			state.buffers.color.setClear( _clearColor.r, _clearColor.g, _clearColor.b, _clearAlpha, _premultipliedAlpha );

		} else if ( background && background.isColor ) {

			state.buffers.color.setClear( background.r, background.g, background.b, 1, _premultipliedAlpha );
			forceClear = true;

		}

		if ( this.autoClear || forceClear ) {

			this.clear( this.autoClearColor, this.autoClearDepth, this.autoClearStencil );

		}

		if ( background && background.isCubeTexture ) {

			if ( backgroundBoxCamera === undefined ) {

				backgroundBoxCamera = new PerspectiveCamera();

				backgroundBoxMesh = new Mesh(
					new BoxBufferGeometry( 5, 5, 5 ),
					new ShaderMaterial( {
						uniforms: ShaderLib.cube.uniforms,
						vertexShader: ShaderLib.cube.vertexShader,
						fragmentShader: ShaderLib.cube.fragmentShader,
						side: BackSide,
						depthTest: false,
						depthWrite: false,
						fog: false
					} )
				);

			}

			backgroundBoxCamera.projectionMatrix.copy( camera.projectionMatrix );

			backgroundBoxCamera.matrixWorld.extractRotation( camera.matrixWorld );
			backgroundBoxCamera.matrixWorldInverse.getInverse( backgroundBoxCamera.matrixWorld );


			backgroundBoxMesh.material.uniforms[ "tCube" ].value = background;
			backgroundBoxMesh.modelViewMatrix.multiplyMatrices( backgroundBoxCamera.matrixWorldInverse, backgroundBoxMesh.matrixWorld );

			objects.update( backgroundBoxMesh );

			_this.renderBufferDirect( backgroundBoxCamera, null, backgroundBoxMesh.geometry, backgroundBoxMesh.material, backgroundBoxMesh, null );

		} else if ( background && background.isTexture ) {

			if ( backgroundPlaneCamera === undefined ) {

				backgroundPlaneCamera = new OrthographicCamera( - 1, 1, 1, - 1, 0, 1 );

				backgroundPlaneMesh = new Mesh(
					new PlaneBufferGeometry( 2, 2 ),
					new MeshBasicMaterial( { depthTest: false, depthWrite: false, fog: false } )
				);

			}

			backgroundPlaneMesh.material.map = background;

			objects.update( backgroundPlaneMesh );

			_this.renderBufferDirect( backgroundPlaneCamera, null, backgroundPlaneMesh.geometry, backgroundPlaneMesh.material, backgroundPlaneMesh, null );

		}

		//

		if ( scene.overrideMaterial ) {

			var overrideMaterial = scene.overrideMaterial;

			renderObjects( opaqueObjects, scene, camera, overrideMaterial );
			renderObjects( transparentObjects, scene, camera, overrideMaterial );

		} else {

			// opaque pass (front-to-back order)

			state.setBlending( NoBlending );
			renderObjects( opaqueObjects, scene, camera );

			// transparent pass (back-to-front order)

			renderObjects( transparentObjects, scene, camera );

		}

		// custom render plugins (post pass)

		spritePlugin.render( scene, camera );
		lensFlarePlugin.render( scene, camera, _currentViewport );

		// Generate mipmap if we're using any kind of mipmap filtering

		if ( renderTarget ) {

			textures.updateRenderTargetMipmap( renderTarget );

		}

		// Ensure depth buffer writing is enabled so it can be cleared on next render

		state.setDepthTest( true );
		state.setDepthWrite( true );
		state.setColorWrite( true );

		// _gl.finish();

	};

	function pushRenderItem( object, geometry, material, z, group ) {

		var array, index;

		// allocate the next position in the appropriate array

		if ( material.transparent ) {

			array = transparentObjects;
			index = ++ transparentObjectsLastIndex;

		} else {

			array = opaqueObjects;
			index = ++ opaqueObjectsLastIndex;

		}

		// recycle existing render item or grow the array

		var renderItem = array[ index ];

		if ( renderItem !== undefined ) {

			renderItem.id = object.id;
			renderItem.object = object;
			renderItem.geometry = geometry;
			renderItem.material = material;
			renderItem.z = _vector3.z;
			renderItem.group = group;

		} else {

			renderItem = {
				id: object.id,
				object: object,
				geometry: geometry,
				material: material,
				z: _vector3.z,
				group: group
			};

			// assert( index === array.length );
			array.push( renderItem );

		}

	}

	// TODO Duplicated code (Frustum)

	function isObjectViewable( object ) {

		var geometry = object.geometry;

		if ( geometry.boundingSphere === null )
			geometry.computeBoundingSphere();

		_sphere.copy( geometry.boundingSphere ).
		applyMatrix4( object.matrixWorld );

		return isSphereViewable( _sphere );

	}

	function isSpriteViewable( sprite ) {

		_sphere.center.set( 0, 0, 0 );
		_sphere.radius = 0.7071067811865476;
		_sphere.applyMatrix4( sprite.matrixWorld );

		return isSphereViewable( _sphere );

	}

	function isSphereViewable( sphere ) {

		if ( ! _frustum.intersectsSphere( sphere ) ) return false;

		var numPlanes = _clipping.numPlanes;

		if ( numPlanes === 0 ) return true;

		var planes = _this.clippingPlanes,

			center = sphere.center,
			negRad = - sphere.radius,
			i = 0;

		do {

			// out when deeper than radius in the negative halfspace
			if ( planes[ i ].distanceToPoint( center ) < negRad ) return false;

		} while ( ++ i !== numPlanes );

		return true;

	}

	function projectObject( object, camera ) {

		if ( object.visible === false ) return;

		var visible = ( object.layers.mask & camera.layers.mask ) !== 0;

		if ( visible ) {

			if ( object.isLight ) {

				lights.push( object );

			} else if ( object.isSprite ) {

				if ( object.frustumCulled === false || isSpriteViewable( object ) === true ) {

					sprites.push( object );

				}

			} else if ( object.isLensFlare ) {

				lensFlares.push( object );

			} else if ( object.isImmediateRenderObject ) {

				if ( _this.sortObjects === true ) {

					_vector3.setFromMatrixPosition( object.matrixWorld );
					_vector3.applyMatrix4( _projScreenMatrix );

				}

				pushRenderItem( object, null, object.material, _vector3.z, null );

			} else if ( object.isMesh || object.isLine || object.isPoints ) {

				if ( object.isSkinnedMesh ) {

					object.skeleton.update();

				}

				if ( object.frustumCulled === false || isObjectViewable( object ) === true ) {

					var material = object.material;

					if ( material.visible === true ) {

						if ( _this.sortObjects === true ) {

							_vector3.setFromMatrixPosition( object.matrixWorld );
							_vector3.applyMatrix4( _projScreenMatrix );

						}

						var geometry = objects.update( object );

						if ( material.isMultiMaterial ) {

							var groups = geometry.groups;
							var materials = material.materials;

							for ( var i = 0, l = groups.length; i < l; i ++ ) {

								var group = groups[ i ];
								var groupMaterial = materials[ group.materialIndex ];

								if ( groupMaterial.visible === true ) {

									pushRenderItem( object, geometry, groupMaterial, _vector3.z, group );

								}

							}

						} else {

							pushRenderItem( object, geometry, material, _vector3.z, null );

						}

					}

				}

			}

		}

		var children = object.children;

		for ( var i = 0, l = children.length; i < l; i ++ ) {

			projectObject( children[ i ], camera );

		}

	}

	function renderObjects( renderList, scene, camera, overrideMaterial ) {

		for ( var i = 0, l = renderList.length; i < l; i ++ ) {

			var renderItem = renderList[ i ];

			var object = renderItem.object;
			var geometry = renderItem.geometry;
			var material = overrideMaterial === undefined ? renderItem.material : overrideMaterial;
			var group = renderItem.group;

			object.modelViewMatrix.multiplyMatrices( camera.matrixWorldInverse, object.matrixWorld );
			object.normalMatrix.getNormalMatrix( object.modelViewMatrix );

			object.onBeforeRender( _this, scene, camera, geometry, material, group );

			if ( object.isImmediateRenderObject ) {

				setMaterial( material );

				var program = setProgram( camera, scene.fog, material, object );

				_currentGeometryProgram = '';

				object.render( function ( object ) {

					_this.renderBufferImmediate( object, program, material );

				} );

			} else {

				_this.renderBufferDirect( camera, scene.fog, geometry, material, object, group );

			}

			object.onAfterRender( _this, scene, camera, geometry, material, group );


		}

	}

	function initMaterial( material, fog, object ) {

		var materialProperties = properties.get( material );

		var parameters = programCache.getParameters(
			material, _lights, fog, _clipping.numPlanes, _clipping.numIntersection, object );

		var code = programCache.getProgramCode( material, parameters );

		var program = materialProperties.program;
		var programChange = true;

		if ( program === undefined ) {

			// new material
			material.addEventListener( 'dispose', onMaterialDispose );

		} else if ( program.code !== code ) {

			// changed glsl or parameters
			releaseMaterialProgramReference( material );

		} else if ( parameters.shaderID !== undefined ) {

			// same glsl and uniform list
			return;

		} else {

			// only rebuild uniform list
			programChange = false;

		}

		if ( programChange ) {

			if ( parameters.shaderID ) {

				var shader = ShaderLib[ parameters.shaderID ];

				materialProperties.__webglShader = {
					name: material.type,
					uniforms: UniformsUtils.clone( shader.uniforms ),
					vertexShader: shader.vertexShader,
					fragmentShader: shader.fragmentShader
				};

			} else {

				materialProperties.__webglShader = {
					name: material.type,
					uniforms: material.uniforms,
					vertexShader: material.vertexShader,
					fragmentShader: material.fragmentShader
				};

			}

			material.__webglShader = materialProperties.__webglShader;

			program = programCache.acquireProgram( material, parameters, code );

			materialProperties.program = program;
			material.program = program;

		}

		var attributes = program.getAttributes();

		if ( material.morphTargets ) {

			material.numSupportedMorphTargets = 0;

			for ( var i = 0; i < _this.maxMorphTargets; i ++ ) {

				if ( attributes[ 'morphTarget' + i ] >= 0 ) {

					material.numSupportedMorphTargets ++;

				}

			}

		}

		if ( material.morphNormals ) {

			material.numSupportedMorphNormals = 0;

			for ( var i = 0; i < _this.maxMorphNormals; i ++ ) {

				if ( attributes[ 'morphNormal' + i ] >= 0 ) {

					material.numSupportedMorphNormals ++;

				}

			}

		}

		var uniforms = materialProperties.__webglShader.uniforms;

		if ( ! material.isShaderMaterial &&
			! material.isRawShaderMaterial ||
			material.clipping === true ) {

			materialProperties.numClippingPlanes = _clipping.numPlanes;
			materialProperties.numIntersection = _clipping.numIntersection;
			uniforms.clippingPlanes = _clipping.uniform;

		}

		materialProperties.fog = fog;

		// store the light setup it was created for

		materialProperties.lightsHash = _lights.hash;

		if ( material.lights ) {

			// wire up the material to this renderer's lighting state

			uniforms.ambientLightColor.value = _lights.ambient;
			uniforms.directionalLights.value = _lights.directional;
			uniforms.spotLights.value = _lights.spot;
			uniforms.rectAreaLights.value = _lights.rectArea;
			uniforms.pointLights.value = _lights.point;
			uniforms.hemisphereLights.value = _lights.hemi;

			uniforms.directionalShadowMap.value = _lights.directionalShadowMap;
			uniforms.directionalShadowMatrix.value = _lights.directionalShadowMatrix;
			uniforms.spotShadowMap.value = _lights.spotShadowMap;
			uniforms.spotShadowMatrix.value = _lights.spotShadowMatrix;
			uniforms.pointShadowMap.value = _lights.pointShadowMap;
			uniforms.pointShadowMatrix.value = _lights.pointShadowMatrix;
			// TODO (abelnation): add area lights shadow info to uniforms

		}

		var progUniforms = materialProperties.program.getUniforms(),
			uniformsList =
				WebGLUniforms.seqWithValue( progUniforms.seq, uniforms );

		materialProperties.uniformsList = uniformsList;

	}

	function setMaterial( material ) {

		material.side === DoubleSide
			? state.disable( _gl.CULL_FACE )
			: state.enable( _gl.CULL_FACE );

		state.setFlipSided( material.side === BackSide );

		material.transparent === true
			? state.setBlending( material.blending, material.blendEquation, material.blendSrc, material.blendDst, material.blendEquationAlpha, material.blendSrcAlpha, material.blendDstAlpha, material.premultipliedAlpha )
			: state.setBlending( NoBlending );

		state.setDepthFunc( material.depthFunc );
		state.setDepthTest( material.depthTest );
		state.setDepthWrite( material.depthWrite );
		state.setColorWrite( material.colorWrite );
		state.setPolygonOffset( material.polygonOffset, material.polygonOffsetFactor, material.polygonOffsetUnits );

	}

	function setProgram( camera, fog, material, object ) {

		_usedTextureUnits = 0;

		var materialProperties = properties.get( material );

		if ( _clippingEnabled ) {

			if ( _localClippingEnabled || camera !== _currentCamera ) {

				var useCache =
					camera === _currentCamera &&
					material.id === _currentMaterialId;

				// we might want to call this function with some ClippingGroup
				// object instead of the material, once it becomes feasible
				// (#8465, #8379)
				_clipping.setState(
					material.clippingPlanes, material.clipIntersection, material.clipShadows,
					camera, materialProperties, useCache );

			}

		}

		if ( material.needsUpdate === false ) {

			if ( materialProperties.program === undefined ) {

				material.needsUpdate = true;

			} else if ( material.fog && materialProperties.fog !== fog ) {

				material.needsUpdate = true;

			} else if ( material.lights && materialProperties.lightsHash !== _lights.hash ) {

				material.needsUpdate = true;

			} else if ( materialProperties.numClippingPlanes !== undefined &&
				( materialProperties.numClippingPlanes !== _clipping.numPlanes ||
				materialProperties.numIntersection  !== _clipping.numIntersection ) ) {

				material.needsUpdate = true;

			}

		}

		if ( material.needsUpdate ) {

			initMaterial( material, fog, object );
			material.needsUpdate = false;

		}

		var refreshProgram = false;
		var refreshMaterial = false;
		var refreshLights = false;

		var program = materialProperties.program,
			p_uniforms = program.getUniforms(),
			m_uniforms = materialProperties.__webglShader.uniforms;

		if ( program.id !== _currentProgram ) {

			_gl.useProgram( program.program );
			_currentProgram = program.id;

			refreshProgram = true;
			refreshMaterial = true;
			refreshLights = true;

		}

		if ( material.id !== _currentMaterialId ) {

			_currentMaterialId = material.id;

			refreshMaterial = true;

		}

		if ( refreshProgram || camera !== _currentCamera ) {

			p_uniforms.set( _gl, camera, 'projectionMatrix' );

			if ( capabilities.logarithmicDepthBuffer ) {

				p_uniforms.setValue( _gl, 'logDepthBufFC',
					2.0 / ( Math.log( camera.far + 1.0 ) / Math.LN2 ) );

			}


			if ( camera !== _currentCamera ) {

				_currentCamera = camera;

				// lighting uniforms depend on the camera so enforce an update
				// now, in case this material supports lights - or later, when
				// the next material that does gets activated:

				refreshMaterial = true;		// set to true on material change
				refreshLights = true;		// remains set until update done

			}

			// load material specific uniforms
			// (shader material also gets them for the sake of genericity)

			if ( material.isShaderMaterial ||
				material.isMeshPhongMaterial ||
				material.isMeshStandardMaterial ||
				material.envMap ) {

				var uCamPos = p_uniforms.map.cameraPosition;

				if ( uCamPos !== undefined ) {

					uCamPos.setValue( _gl,
						_vector3.setFromMatrixPosition( camera.matrixWorld ) );

				}

			}

			if ( material.isMeshPhongMaterial ||
				material.isMeshLambertMaterial ||
				material.isMeshBasicMaterial ||
				material.isMeshStandardMaterial ||
				material.isShaderMaterial ||
				material.skinning ) {

				p_uniforms.setValue( _gl, 'viewMatrix', camera.matrixWorldInverse );

			}

			p_uniforms.set( _gl, _this, 'toneMappingExposure' );
			p_uniforms.set( _gl, _this, 'toneMappingWhitePoint' );

		}

		// skinning uniforms must be set even if material didn't change
		// auto-setting of texture unit for bone texture must go before other textures
		// not sure why, but otherwise weird things happen

		if ( material.skinning ) {

			p_uniforms.setOptional( _gl, object, 'bindMatrix' );
			p_uniforms.setOptional( _gl, object, 'bindMatrixInverse' );

			var skeleton = object.skeleton;

			if ( skeleton ) {

				if ( capabilities.floatVertexTextures && skeleton.useVertexTexture ) {

					p_uniforms.set( _gl, skeleton, 'boneTexture' );
					p_uniforms.set( _gl, skeleton, 'boneTextureWidth' );
					p_uniforms.set( _gl, skeleton, 'boneTextureHeight' );

				} else {

					p_uniforms.setOptional( _gl, skeleton, 'boneMatrices' );

				}

			}

		}

		if ( refreshMaterial ) {

			if ( material.lights ) {

				// the current material requires lighting info

				// note: all lighting uniforms are always set correctly
				// they simply reference the renderer's state for their
				// values
				//
				// use the current material's .needsUpdate flags to set
				// the GL state when required

				markUniformsLightsNeedsUpdate( m_uniforms, refreshLights );

			}

			// refresh uniforms common to several materials

			if ( fog && material.fog ) {

				refreshUniformsFog( m_uniforms, fog );

			}

			if ( material.isMeshBasicMaterial ||
				material.isMeshLambertMaterial ||
				material.isMeshPhongMaterial ||
				material.isMeshStandardMaterial ||
				material.isMeshNormalMaterial ||
				material.isMeshDepthMaterial ) {

				refreshUniformsCommon( m_uniforms, material );

			}

			// refresh single material specific uniforms

			if ( material.isLineBasicMaterial ) {

				refreshUniformsLine( m_uniforms, material );

			} else if ( material.isLineDashedMaterial ) {

				refreshUniformsLine( m_uniforms, material );
				refreshUniformsDash( m_uniforms, material );

			} else if ( material.isPointsMaterial ) {

				refreshUniformsPoints( m_uniforms, material );

			} else if ( material.isMeshLambertMaterial ) {

				refreshUniformsLambert( m_uniforms, material );

			} else if ( material.isMeshToonMaterial ) {

				refreshUniformsToon( m_uniforms, material );

			} else if ( material.isMeshPhongMaterial ) {

				refreshUniformsPhong( m_uniforms, material );

			} else if ( material.isMeshPhysicalMaterial ) {

				refreshUniformsPhysical( m_uniforms, material );

			} else if ( material.isMeshStandardMaterial ) {

				refreshUniformsStandard( m_uniforms, material );

			} else if ( material.isMeshDepthMaterial ) {

				if ( material.displacementMap ) {

					m_uniforms.displacementMap.value = material.displacementMap;
					m_uniforms.displacementScale.value = material.displacementScale;
					m_uniforms.displacementBias.value = material.displacementBias;

				}

			} else if ( material.isMeshNormalMaterial ) {

				refreshUniformsNormal( m_uniforms, material );

			}

			// RectAreaLight Texture
			// TODO (mrdoob): Find a nicer implementation

			if ( m_uniforms.ltcMat !== undefined ) m_uniforms.ltcMat.value = THREE.UniformsLib.LTC_MAT_TEXTURE;
			if ( m_uniforms.ltcMag !== undefined ) m_uniforms.ltcMag.value = THREE.UniformsLib.LTC_MAG_TEXTURE;

			WebGLUniforms.upload(
				_gl, materialProperties.uniformsList, m_uniforms, _this );

		}


		// common matrices

		p_uniforms.set( _gl, object, 'modelViewMatrix' );
		p_uniforms.set( _gl, object, 'normalMatrix' );
		p_uniforms.setValue( _gl, 'modelMatrix', object.matrixWorld );

		return program;

	}

	// Uniforms (refresh uniforms objects)

	function refreshUniformsCommon( uniforms, material ) {

		uniforms.opacity.value = material.opacity;

		uniforms.diffuse.value = material.color;

		if ( material.emissive ) {

			uniforms.emissive.value.copy( material.emissive ).multiplyScalar( material.emissiveIntensity );

		}

		uniforms.map.value = material.map;
		uniforms.specularMap.value = material.specularMap;
		uniforms.alphaMap.value = material.alphaMap;

		if ( material.lightMap ) {

			uniforms.lightMap.value = material.lightMap;
			uniforms.lightMapIntensity.value = material.lightMapIntensity;

		}

		if ( material.aoMap ) {

			uniforms.aoMap.value = material.aoMap;
			uniforms.aoMapIntensity.value = material.aoMapIntensity;

		}

		// uv repeat and offset setting priorities
		// 1. color map
		// 2. specular map
		// 3. normal map
		// 4. bump map
		// 5. alpha map
		// 6. emissive map

		var uvScaleMap;

		if ( material.map ) {

			uvScaleMap = material.map;

		} else if ( material.specularMap ) {

			uvScaleMap = material.specularMap;

		} else if ( material.displacementMap ) {

			uvScaleMap = material.displacementMap;

		} else if ( material.normalMap ) {

			uvScaleMap = material.normalMap;

		} else if ( material.bumpMap ) {

			uvScaleMap = material.bumpMap;

		} else if ( material.roughnessMap ) {

			uvScaleMap = material.roughnessMap;

		} else if ( material.metalnessMap ) {

			uvScaleMap = material.metalnessMap;

		} else if ( material.alphaMap ) {

			uvScaleMap = material.alphaMap;

		} else if ( material.emissiveMap ) {

			uvScaleMap = material.emissiveMap;

		}

		if ( uvScaleMap !== undefined ) {

			// backwards compatibility
			if ( uvScaleMap.isWebGLRenderTarget ) {

				uvScaleMap = uvScaleMap.texture;

			}

			var offset = uvScaleMap.offset;
			var repeat = uvScaleMap.repeat;

			uniforms.offsetRepeat.value.set( offset.x, offset.y, repeat.x, repeat.y );

		}

		uniforms.envMap.value = material.envMap;

		// don't flip CubeTexture envMaps, flip everything else:
		//  WebGLRenderTargetCube will be flipped for backwards compatibility
		//  WebGLRenderTargetCube.texture will be flipped because it's a Texture and NOT a CubeTexture
		// this check must be handled differently, or removed entirely, if WebGLRenderTargetCube uses a CubeTexture in the future
		uniforms.flipEnvMap.value = ( ! ( material.envMap && material.envMap.isCubeTexture ) ) ? 1 : - 1;

		uniforms.reflectivity.value = material.reflectivity;
		uniforms.refractionRatio.value = material.refractionRatio;

	}

	function refreshUniformsLine( uniforms, material ) {

		uniforms.diffuse.value = material.color;
		uniforms.opacity.value = material.opacity;

	}

	function refreshUniformsDash( uniforms, material ) {

		uniforms.dashSize.value = material.dashSize;
		uniforms.totalSize.value = material.dashSize + material.gapSize;
		uniforms.scale.value = material.scale;

	}

	function refreshUniformsPoints( uniforms, material ) {

		uniforms.diffuse.value = material.color;
		uniforms.opacity.value = material.opacity;
		uniforms.size.value = material.size * _pixelRatio;
		uniforms.scale.value = _height * 0.5;

		uniforms.map.value = material.map;

		if ( material.map !== null ) {

			var offset = material.map.offset;
			var repeat = material.map.repeat;

			uniforms.offsetRepeat.value.set( offset.x, offset.y, repeat.x, repeat.y );

		}

	}

	function refreshUniformsFog( uniforms, fog ) {

		uniforms.fogColor.value = fog.color;

		if ( fog.isFog ) {

			uniforms.fogNear.value = fog.near;
			uniforms.fogFar.value = fog.far;

		} else if ( fog.isFogExp2 ) {

			uniforms.fogDensity.value = fog.density;

		}

	}

	function refreshUniformsLambert( uniforms, material ) {

		if ( material.emissiveMap ) {

			uniforms.emissiveMap.value = material.emissiveMap;

		}

	}

	function refreshUniformsPhong( uniforms, material ) {

		uniforms.specular.value = material.specular;
		uniforms.shininess.value = Math.max( material.shininess, 1e-4 ); // to prevent pow( 0.0, 0.0 )

		if ( material.emissiveMap ) {

			uniforms.emissiveMap.value = material.emissiveMap;

		}

		if ( material.bumpMap ) {

			uniforms.bumpMap.value = material.bumpMap;
			uniforms.bumpScale.value = material.bumpScale;

		}

		if ( material.normalMap ) {

			uniforms.normalMap.value = material.normalMap;
			uniforms.normalScale.value.copy( material.normalScale );

		}

		if ( material.displacementMap ) {

			uniforms.displacementMap.value = material.displacementMap;
			uniforms.displacementScale.value = material.displacementScale;
			uniforms.displacementBias.value = material.displacementBias;

		}

	}

	function refreshUniformsToon( uniforms, material ) {

		refreshUniformsPhong( uniforms, material );

		if ( material.gradientMap ) {

			uniforms.gradientMap.value = material.gradientMap;

		}

	}

	function refreshUniformsStandard( uniforms, material ) {

		uniforms.roughness.value = material.roughness;
		uniforms.metalness.value = material.metalness;

		if ( material.roughnessMap ) {

			uniforms.roughnessMap.value = material.roughnessMap;

		}

		if ( material.metalnessMap ) {

			uniforms.metalnessMap.value = material.metalnessMap;

		}

		if ( material.emissiveMap ) {

			uniforms.emissiveMap.value = material.emissiveMap;

		}

		if ( material.bumpMap ) {

			uniforms.bumpMap.value = material.bumpMap;
			uniforms.bumpScale.value = material.bumpScale;

		}

		if ( material.normalMap ) {

			uniforms.normalMap.value = material.normalMap;
			uniforms.normalScale.value.copy( material.normalScale );

		}

		if ( material.displacementMap ) {

			uniforms.displacementMap.value = material.displacementMap;
			uniforms.displacementScale.value = material.displacementScale;
			uniforms.displacementBias.value = material.displacementBias;

		}

		if ( material.envMap ) {

			//uniforms.envMap.value = material.envMap; // part of uniforms common
			uniforms.envMapIntensity.value = material.envMapIntensity;

		}

	}

	function refreshUniformsPhysical( uniforms, material ) {

		uniforms.clearCoat.value = material.clearCoat;
		uniforms.clearCoatRoughness.value = material.clearCoatRoughness;

		refreshUniformsStandard( uniforms, material );

	}

	function refreshUniformsNormal( uniforms, material ) {

		if ( material.bumpMap ) {

			uniforms.bumpMap.value = material.bumpMap;
			uniforms.bumpScale.value = material.bumpScale;

		}

		if ( material.normalMap ) {

			uniforms.normalMap.value = material.normalMap;
			uniforms.normalScale.value.copy( material.normalScale );

		}

		if ( material.displacementMap ) {

			uniforms.displacementMap.value = material.displacementMap;
			uniforms.displacementScale.value = material.displacementScale;
			uniforms.displacementBias.value = material.displacementBias;

		}

	}

	// If uniforms are marked as clean, they don't need to be loaded to the GPU.

	function markUniformsLightsNeedsUpdate( uniforms, value ) {

		uniforms.ambientLightColor.needsUpdate = value;

		uniforms.directionalLights.needsUpdate = value;
		uniforms.pointLights.needsUpdate = value;
		uniforms.spotLights.needsUpdate = value;
		uniforms.rectAreaLights.needsUpdate = value;
		uniforms.hemisphereLights.needsUpdate = value;

	}

	// Lighting

	function setupShadows( lights ) {

		var lightShadowsLength = 0;

		for ( var i = 0, l = lights.length; i < l; i ++ ) {

			var light = lights[ i ];

			if ( light.castShadow ) {

				_lights.shadows[ lightShadowsLength ++ ] = light;

			}

		}

		_lights.shadows.length = lightShadowsLength;

	}

	function setupLights( lights, camera ) {

		var l, ll, light,
			r = 0, g = 0, b = 0,
			color,
			intensity,
			distance,
			shadowMap,

			viewMatrix = camera.matrixWorldInverse,

		directionalLength = 0,
		pointLength = 0,
		spotLength = 0,
		rectAreaLength = 0,
		hemiLength = 0;

		for ( l = 0, ll = lights.length; l < ll; l ++ ) {

			light = lights[ l ];

			color = light.color;
			intensity = light.intensity;
			distance = light.distance;

			shadowMap = ( light.shadow && light.shadow.map ) ? light.shadow.map.texture : null;

			if ( light.isAmbientLight ) {

				r += color.r * intensity;
				g += color.g * intensity;
				b += color.b * intensity;

			} else if ( light.isDirectionalLight ) {

				var uniforms = lightCache.get( light );

				uniforms.color.copy( light.color ).multiplyScalar( light.intensity );
				uniforms.direction.setFromMatrixPosition( light.matrixWorld );
				_vector3.setFromMatrixPosition( light.target.matrixWorld );
				uniforms.direction.sub( _vector3 );
				uniforms.direction.transformDirection( viewMatrix );

				uniforms.shadow = light.castShadow;

				if ( light.castShadow ) {

					uniforms.shadowBias = light.shadow.bias;
					uniforms.shadowRadius = light.shadow.radius;
					uniforms.shadowMapSize = light.shadow.mapSize;

				}

				_lights.directionalShadowMap[ directionalLength ] = shadowMap;
				_lights.directionalShadowMatrix[ directionalLength ] = light.shadow.matrix;
				_lights.directional[ directionalLength ++ ] = uniforms;

			} else if ( light.isSpotLight ) {

				var uniforms = lightCache.get( light );

				uniforms.position.setFromMatrixPosition( light.matrixWorld );
				uniforms.position.applyMatrix4( viewMatrix );

				uniforms.color.copy( color ).multiplyScalar( intensity );
				uniforms.distance = distance;

				uniforms.direction.setFromMatrixPosition( light.matrixWorld );
				_vector3.setFromMatrixPosition( light.target.matrixWorld );
				uniforms.direction.sub( _vector3 );
				uniforms.direction.transformDirection( viewMatrix );

				uniforms.coneCos = Math.cos( light.angle );
				uniforms.penumbraCos = Math.cos( light.angle * ( 1 - light.penumbra ) );
				uniforms.decay = ( light.distance === 0 ) ? 0.0 : light.decay;

				uniforms.shadow = light.castShadow;

				if ( light.castShadow ) {

					uniforms.shadowBias = light.shadow.bias;
					uniforms.shadowRadius = light.shadow.radius;
					uniforms.shadowMapSize = light.shadow.mapSize;

				}

				_lights.spotShadowMap[ spotLength ] = shadowMap;
				_lights.spotShadowMatrix[ spotLength ] = light.shadow.matrix;
				_lights.spot[ spotLength ++ ] = uniforms;

			} else if ( light.isRectAreaLight ) {

				var uniforms = lightCache.get( light );

				// (a) intensity controls irradiance of entire light
				uniforms.color
					.copy( color )
					.multiplyScalar( intensity / ( light.width * light.height ) );

				// (b) intensity controls the radiance per light area
				// uniforms.color.copy( color ).multiplyScalar( intensity );

				uniforms.position.setFromMatrixPosition( light.matrixWorld );
				uniforms.position.applyMatrix4( viewMatrix );

				// extract local rotation of light to derive width/height half vectors
				_matrix42.identity();
				_matrix4.copy( light.matrixWorld );
				_matrix4.premultiply( viewMatrix );
				_matrix42.extractRotation( _matrix4 );

				uniforms.halfWidth.set( light.width * 0.5,                0.0, 0.0 );
				uniforms.halfHeight.set(              0.0, light.height * 0.5, 0.0 );

				uniforms.halfWidth.applyMatrix4( _matrix42 );
				uniforms.halfHeight.applyMatrix4( _matrix42 );

				// TODO (abelnation): RectAreaLight distance?
				// uniforms.distance = distance;

				_lights.rectArea[ rectAreaLength ++ ] = uniforms;

			} else if ( light.isPointLight ) {

				var uniforms = lightCache.get( light );

				uniforms.position.setFromMatrixPosition( light.matrixWorld );
				uniforms.position.applyMatrix4( viewMatrix );

				uniforms.color.copy( light.color ).multiplyScalar( light.intensity );
				uniforms.distance = light.distance;
				uniforms.decay = ( light.distance === 0 ) ? 0.0 : light.decay;

				uniforms.shadow = light.castShadow;

				if ( light.castShadow ) {

					uniforms.shadowBias = light.shadow.bias;
					uniforms.shadowRadius = light.shadow.radius;
					uniforms.shadowMapSize = light.shadow.mapSize;

				}

				_lights.pointShadowMap[ pointLength ] = shadowMap;

				if ( _lights.pointShadowMatrix[ pointLength ] === undefined ) {

					_lights.pointShadowMatrix[ pointLength ] = new Matrix4();

				}

				// for point lights we set the shadow matrix to be a translation-only matrix
				// equal to inverse of the light's position
				_vector3.setFromMatrixPosition( light.matrixWorld ).negate();
				_lights.pointShadowMatrix[ pointLength ].identity().setPosition( _vector3 );

				_lights.point[ pointLength ++ ] = uniforms;

			} else if ( light.isHemisphereLight ) {

				var uniforms = lightCache.get( light );

				uniforms.direction.setFromMatrixPosition( light.matrixWorld );
				uniforms.direction.transformDirection( viewMatrix );
				uniforms.direction.normalize();

				uniforms.skyColor.copy( light.color ).multiplyScalar( intensity );
				uniforms.groundColor.copy( light.groundColor ).multiplyScalar( intensity );

				_lights.hemi[ hemiLength ++ ] = uniforms;

			}

		}

		_lights.ambient[ 0 ] = r;
		_lights.ambient[ 1 ] = g;
		_lights.ambient[ 2 ] = b;

		_lights.directional.length = directionalLength;
		_lights.spot.length = spotLength;
		_lights.rectArea.length = rectAreaLength;
		_lights.point.length = pointLength;
		_lights.hemi.length = hemiLength;

		// TODO (sam-g-steel) why aren't we using join
		_lights.hash = directionalLength + ',' + pointLength + ',' + spotLength + ',' + rectAreaLength + ',' + hemiLength + ',' + _lights.shadows.length;

	}

	// GL state setting

	this.setFaceCulling = function ( cullFace, frontFaceDirection ) {

		state.setCullFace( cullFace );
		state.setFlipSided( frontFaceDirection === FrontFaceDirectionCW );

	};

	// Textures

	function allocTextureUnit() {

		var textureUnit = _usedTextureUnits;

		if ( textureUnit >= capabilities.maxTextures ) {

			console.warn( 'WebGLRenderer: trying to use ' + textureUnit + ' texture units while this GPU supports only ' + capabilities.maxTextures );

		}

		_usedTextureUnits += 1;

		return textureUnit;

	}

	this.allocTextureUnit = allocTextureUnit;

	// this.setTexture2D = setTexture2D;
	this.setTexture2D = ( function() {

		var warned = false;

		// backwards compatibility: peel texture.texture
		return function setTexture2D( texture, slot ) {

			if ( texture && texture.isWebGLRenderTarget ) {

				if ( ! warned ) {

					console.warn( "THREE.WebGLRenderer.setTexture2D: don't use render targets as textures. Use their .texture property instead." );
					warned = true;

				}

				texture = texture.texture;

			}

			textures.setTexture2D( texture, slot );

		};

	}() );

	this.setTexture = ( function() {

		var warned = false;

		return function setTexture( texture, slot ) {

			if ( ! warned ) {

				console.warn( "THREE.WebGLRenderer: .setTexture is deprecated, use setTexture2D instead." );
				warned = true;

			}

			textures.setTexture2D( texture, slot );

		};

	}() );

	this.setTextureCube = ( function() {

		var warned = false;

		return function setTextureCube( texture, slot ) {

			// backwards compatibility: peel texture.texture
			if ( texture && texture.isWebGLRenderTargetCube ) {

				if ( ! warned ) {

					console.warn( "THREE.WebGLRenderer.setTextureCube: don't use cube render targets as textures. Use their .texture property instead." );
					warned = true;

				}

				texture = texture.texture;

			}

			// currently relying on the fact that WebGLRenderTargetCube.texture is a Texture and NOT a CubeTexture
			// TODO: unify these code paths
			if ( ( texture && texture.isCubeTexture ) ||
				( Array.isArray( texture.image ) && texture.image.length === 6 ) ) {

				// CompressedTexture can have Array in image :/

				// this function alone should take care of cube textures
				textures.setTextureCube( texture, slot );

			} else {

				// assumed: texture property of THREE.WebGLRenderTargetCube

				textures.setTextureCubeDynamic( texture, slot );

			}

		};

	}() );

	this.getCurrentRenderTarget = function() {

		return _currentRenderTarget;

	};

	this.setRenderTarget = function ( renderTarget ) {

		_currentRenderTarget = renderTarget;

		if ( renderTarget && properties.get( renderTarget ).__webglFramebuffer === undefined ) {

			textures.setupRenderTarget( renderTarget );

		}

		var isCube = ( renderTarget && renderTarget.isWebGLRenderTargetCube );
		var framebuffer;

		if ( renderTarget ) {

			var renderTargetProperties = properties.get( renderTarget );

			if ( isCube ) {

				framebuffer = renderTargetProperties.__webglFramebuffer[ renderTarget.activeCubeFace ];

			} else {

				framebuffer = renderTargetProperties.__webglFramebuffer;

			}

			_currentScissor.copy( renderTarget.scissor );
			_currentScissorTest = renderTarget.scissorTest;

			_currentViewport.copy( renderTarget.viewport );

		} else {

			framebuffer = null;

			_currentScissor.copy( _scissor ).multiplyScalar( _pixelRatio );
			_currentScissorTest = _scissorTest;

			_currentViewport.copy( _viewport ).multiplyScalar( _pixelRatio );

		}

		if ( _currentFramebuffer !== framebuffer ) {

			_gl.bindFramebuffer( _gl.FRAMEBUFFER, framebuffer );
			_currentFramebuffer = framebuffer;

		}

		state.scissor( _currentScissor );
		state.setScissorTest( _currentScissorTest );

		state.viewport( _currentViewport );

		if ( isCube ) {

			var textureProperties = properties.get( renderTarget.texture );
			_gl.framebufferTexture2D( _gl.FRAMEBUFFER, _gl.COLOR_ATTACHMENT0, _gl.TEXTURE_CUBE_MAP_POSITIVE_X + renderTarget.activeCubeFace, textureProperties.__webglTexture, renderTarget.activeMipMapLevel );

		}

	};

	this.readRenderTargetPixels = function ( renderTarget, x, y, width, height, buffer ) {

		if ( ( renderTarget && renderTarget.isWebGLRenderTarget ) === false ) {

			console.error( 'THREE.WebGLRenderer.readRenderTargetPixels: renderTarget is not THREE.WebGLRenderTarget.' );
			return;

		}

		var framebuffer = properties.get( renderTarget ).__webglFramebuffer;

		if ( framebuffer ) {

			var restore = false;

			if ( framebuffer !== _currentFramebuffer ) {

				_gl.bindFramebuffer( _gl.FRAMEBUFFER, framebuffer );

				restore = true;

			}

			try {

				var texture = renderTarget.texture;
				var textureFormat = texture.format;
				var textureType = texture.type;

				if ( textureFormat !== RGBAFormat && paramThreeToGL( textureFormat ) !== _gl.getParameter( _gl.IMPLEMENTATION_COLOR_READ_FORMAT ) ) {

					console.error( 'THREE.WebGLRenderer.readRenderTargetPixels: renderTarget is not in RGBA or implementation defined format.' );
					return;

				}

				if ( textureType !== UnsignedByteType && paramThreeToGL( textureType ) !== _gl.getParameter( _gl.IMPLEMENTATION_COLOR_READ_TYPE ) && // IE11, Edge and Chrome Mac < 52 (#9513)
					! ( textureType === FloatType && ( extensions.get( 'OES_texture_float' ) || extensions.get( 'WEBGL_color_buffer_float' ) ) ) && // Chrome Mac >= 52 and Firefox
					! ( textureType === HalfFloatType && extensions.get( 'EXT_color_buffer_half_float' ) ) ) {

					console.error( 'THREE.WebGLRenderer.readRenderTargetPixels: renderTarget is not in UnsignedByteType or implementation defined type.' );
					return;

				}

				if ( _gl.checkFramebufferStatus( _gl.FRAMEBUFFER ) === _gl.FRAMEBUFFER_COMPLETE ) {

					// the following if statement ensures valid read requests (no out-of-bounds pixels, see #8604)

					if ( ( x >= 0 && x <= ( renderTarget.width - width ) ) && ( y >= 0 && y <= ( renderTarget.height - height ) ) ) {

						_gl.readPixels( x, y, width, height, paramThreeToGL( textureFormat ), paramThreeToGL( textureType ), buffer );

					}

				} else {

					console.error( 'THREE.WebGLRenderer.readRenderTargetPixels: readPixels from renderTarget failed. Framebuffer not complete.' );

				}

			} finally {

				if ( restore ) {

					_gl.bindFramebuffer( _gl.FRAMEBUFFER, _currentFramebuffer );

				}

			}

		}

	};

	// Map three.js constants to WebGL constants

	function paramThreeToGL( p ) {

		var extension;

		if ( p === RepeatWrapping ) return _gl.REPEAT;
		if ( p === ClampToEdgeWrapping ) return _gl.CLAMP_TO_EDGE;
		if ( p === MirroredRepeatWrapping ) return _gl.MIRRORED_REPEAT;

		if ( p === NearestFilter ) return _gl.NEAREST;
		if ( p === NearestMipMapNearestFilter ) return _gl.NEAREST_MIPMAP_NEAREST;
		if ( p === NearestMipMapLinearFilter ) return _gl.NEAREST_MIPMAP_LINEAR;

		if ( p === LinearFilter ) return _gl.LINEAR;
		if ( p === LinearMipMapNearestFilter ) return _gl.LINEAR_MIPMAP_NEAREST;
		if ( p === LinearMipMapLinearFilter ) return _gl.LINEAR_MIPMAP_LINEAR;

		if ( p === UnsignedByteType ) return _gl.UNSIGNED_BYTE;
		if ( p === UnsignedShort4444Type ) return _gl.UNSIGNED_SHORT_4_4_4_4;
		if ( p === UnsignedShort5551Type ) return _gl.UNSIGNED_SHORT_5_5_5_1;
		if ( p === UnsignedShort565Type ) return _gl.UNSIGNED_SHORT_5_6_5;

		if ( p === ByteType ) return _gl.BYTE;
		if ( p === ShortType ) return _gl.SHORT;
		if ( p === UnsignedShortType ) return _gl.UNSIGNED_SHORT;
		if ( p === IntType ) return _gl.INT;
		if ( p === UnsignedIntType ) return _gl.UNSIGNED_INT;
		if ( p === FloatType ) return _gl.FLOAT;

		if ( p === HalfFloatType ) {

			extension = extensions.get( 'OES_texture_half_float' );

			if ( extension !== null ) return extension.HALF_FLOAT_OES;

		}

		if ( p === AlphaFormat ) return _gl.ALPHA;
		if ( p === RGBFormat ) return _gl.RGB;
		if ( p === RGBAFormat ) return _gl.RGBA;
		if ( p === LuminanceFormat ) return _gl.LUMINANCE;
		if ( p === LuminanceAlphaFormat ) return _gl.LUMINANCE_ALPHA;
		if ( p === DepthFormat ) return _gl.DEPTH_COMPONENT;
		if ( p === DepthStencilFormat ) return _gl.DEPTH_STENCIL;

		if ( p === AddEquation ) return _gl.FUNC_ADD;
		if ( p === SubtractEquation ) return _gl.FUNC_SUBTRACT;
		if ( p === ReverseSubtractEquation ) return _gl.FUNC_REVERSE_SUBTRACT;

		if ( p === ZeroFactor ) return _gl.ZERO;
		if ( p === OneFactor ) return _gl.ONE;
		if ( p === SrcColorFactor ) return _gl.SRC_COLOR;
		if ( p === OneMinusSrcColorFactor ) return _gl.ONE_MINUS_SRC_COLOR;
		if ( p === SrcAlphaFactor ) return _gl.SRC_ALPHA;
		if ( p === OneMinusSrcAlphaFactor ) return _gl.ONE_MINUS_SRC_ALPHA;
		if ( p === DstAlphaFactor ) return _gl.DST_ALPHA;
		if ( p === OneMinusDstAlphaFactor ) return _gl.ONE_MINUS_DST_ALPHA;

		if ( p === DstColorFactor ) return _gl.DST_COLOR;
		if ( p === OneMinusDstColorFactor ) return _gl.ONE_MINUS_DST_COLOR;
		if ( p === SrcAlphaSaturateFactor ) return _gl.SRC_ALPHA_SATURATE;

		if ( p === RGB_S3TC_DXT1_Format || p === RGBA_S3TC_DXT1_Format ||
			p === RGBA_S3TC_DXT3_Format || p === RGBA_S3TC_DXT5_Format ) {

			extension = extensions.get( 'WEBGL_compressed_texture_s3tc' );

			if ( extension !== null ) {

				if ( p === RGB_S3TC_DXT1_Format ) return extension.COMPRESSED_RGB_S3TC_DXT1_EXT;
				if ( p === RGBA_S3TC_DXT1_Format ) return extension.COMPRESSED_RGBA_S3TC_DXT1_EXT;
				if ( p === RGBA_S3TC_DXT3_Format ) return extension.COMPRESSED_RGBA_S3TC_DXT3_EXT;
				if ( p === RGBA_S3TC_DXT5_Format ) return extension.COMPRESSED_RGBA_S3TC_DXT5_EXT;

			}

		}

		if ( p === RGB_PVRTC_4BPPV1_Format || p === RGB_PVRTC_2BPPV1_Format ||
			p === RGBA_PVRTC_4BPPV1_Format || p === RGBA_PVRTC_2BPPV1_Format ) {

			extension = extensions.get( 'WEBGL_compressed_texture_pvrtc' );

			if ( extension !== null ) {

				if ( p === RGB_PVRTC_4BPPV1_Format ) return extension.COMPRESSED_RGB_PVRTC_4BPPV1_IMG;
				if ( p === RGB_PVRTC_2BPPV1_Format ) return extension.COMPRESSED_RGB_PVRTC_2BPPV1_IMG;
				if ( p === RGBA_PVRTC_4BPPV1_Format ) return extension.COMPRESSED_RGBA_PVRTC_4BPPV1_IMG;
				if ( p === RGBA_PVRTC_2BPPV1_Format ) return extension.COMPRESSED_RGBA_PVRTC_2BPPV1_IMG;

			}

		}

		if ( p === RGB_ETC1_Format ) {

			extension = extensions.get( 'WEBGL_compressed_texture_etc1' );

			if ( extension !== null ) return extension.COMPRESSED_RGB_ETC1_WEBGL;

		}

		if ( p === MinEquation || p === MaxEquation ) {

			extension = extensions.get( 'EXT_blend_minmax' );

			if ( extension !== null ) {

				if ( p === MinEquation ) return extension.MIN_EXT;
				if ( p === MaxEquation ) return extension.MAX_EXT;

			}

		}

		if ( p === UnsignedInt248Type ) {

			extension = extensions.get( 'WEBGL_depth_texture' );

			if ( extension !== null ) return extension.UNSIGNED_INT_24_8_WEBGL;

		}

		return 0;

	}

}

/**
 * @author mrdoob / http://mrdoob.com/
 */

function Scene () {

	Object3D.call( this );

	this.type = 'Scene';

	this.background = null;
	this.fog = null;
	this.overrideMaterial = null;

	this.autoUpdate = true; // checked by the renderer

}

Scene.prototype = Object.create( Object3D.prototype );

Scene.prototype.constructor = Scene;

Scene.prototype.copy = function ( source, recursive ) {

	Object3D.prototype.copy.call( this, source, recursive );

	if ( source.background !== null ) this.background = source.background.clone();
	if ( source.fog !== null ) this.fog = source.fog.clone();
	if ( source.overrideMaterial !== null ) this.overrideMaterial = source.overrideMaterial.clone();

	this.autoUpdate = source.autoUpdate;
	this.matrixAutoUpdate = source.matrixAutoUpdate;

	return this;

};

Scene.prototype.toJSON = function ( meta ) {

	var data = Object3D.prototype.toJSON.call( this, meta );

	if ( this.background !== null ) data.object.background = this.background.toJSON( meta );
	if ( this.fog !== null ) data.object.fog = this.fog.toJSON();

	return data;

};

/**
 * @author mrdoob / http://mrdoob.com/
 */

function VideoTexture( video, mapping, wrapS, wrapT, magFilter, minFilter, format, type, anisotropy ) {

	Texture.call( this, video, mapping, wrapS, wrapT, magFilter, minFilter, format, type, anisotropy );

	this.generateMipmaps = false;

	var scope = this;

	function update() {

		requestAnimationFrame( update );

		if ( video.readyState >= video.HAVE_CURRENT_DATA ) {

			scope.needsUpdate = true;

		}

	}

	update();

}

VideoTexture.prototype = Object.create( Texture.prototype );
VideoTexture.prototype.constructor = VideoTexture;

/**
 * @author mrdoob / http://mrdoob.com/
 */

var Cache = {

	enabled: false,

	files: {},

	add: function ( key, file ) {

		if ( this.enabled === false ) return;

		// console.log( 'THREE.Cache', 'Adding key:', key );

		this.files[ key ] = file;

	},

	get: function ( key ) {

		if ( this.enabled === false ) return;

		// console.log( 'THREE.Cache', 'Checking key:', key );

		return this.files[ key ];

	},

	remove: function ( key ) {

		delete this.files[ key ];

	},

	clear: function () {

		this.files = {};

	}

};

/**
 * @author mrdoob / http://mrdoob.com/
 */

function LoadingManager( onLoad, onProgress, onError ) {

	var scope = this;

	var isLoading = false, itemsLoaded = 0, itemsTotal = 0;

	this.onStart = undefined;
	this.onLoad = onLoad;
	this.onProgress = onProgress;
	this.onError = onError;

	this.itemStart = function ( url ) {

		itemsTotal ++;

		if ( isLoading === false ) {

			if ( scope.onStart !== undefined ) {

				scope.onStart( url, itemsLoaded, itemsTotal );

			}

		}

		isLoading = true;

	};

	this.itemEnd = function ( url ) {

		itemsLoaded ++;

		if ( scope.onProgress !== undefined ) {

			scope.onProgress( url, itemsLoaded, itemsTotal );

		}

		if ( itemsLoaded === itemsTotal ) {

			isLoading = false;

			if ( scope.onLoad !== undefined ) {

				scope.onLoad();

			}

		}

	};

	this.itemError = function ( url ) {

		if ( scope.onError !== undefined ) {

			scope.onError( url );

		}

	};

}

var DefaultLoadingManager = new LoadingManager();

/**
 * @author mrdoob / http://mrdoob.com/
 */

function ImageLoader( manager ) {

	this.manager = ( manager !== undefined ) ? manager : DefaultLoadingManager;

}

Object.assign( ImageLoader.prototype, {

	load: function ( url, onLoad, onProgress, onError ) {

		if ( url === undefined ) url = '';

		if ( this.path !== undefined ) url = this.path + url;

		var scope = this;

		var cached = Cache.get( url );

		if ( cached !== undefined ) {

			scope.manager.itemStart( url );

			setTimeout( function () {

				if ( onLoad ) onLoad( cached );

				scope.manager.itemEnd( url );

			}, 0 );

			return cached;

		}

		var image = document.createElementNS( 'http://www.w3.org/1999/xhtml', 'img' );

		image.addEventListener( 'load', function () {

			Cache.add( url, this );

			if ( onLoad ) onLoad( this );

			scope.manager.itemEnd( url );

		}, false );

		/*
		image.addEventListener( 'progress', function ( event ) {

			if ( onProgress ) onProgress( event );

		}, false );
		*/

		image.addEventListener( 'error', function ( event ) {

			if ( onError ) onError( event );

			scope.manager.itemError( url );

		}, false );

		if ( this.crossOrigin !== undefined ) image.crossOrigin = this.crossOrigin;

		scope.manager.itemStart( url );

		image.src = url;

		return image;

	},

	setCrossOrigin: function ( value ) {

		this.crossOrigin = value;
		return this;

	},

	setPath: function ( value ) {

		this.path = value;
		return this;

	}

} );

/**
 * @author mrdoob / http://mrdoob.com/
 */

function TextureLoader( manager ) {

	this.manager = ( manager !== undefined ) ? manager : DefaultLoadingManager;

}

Object.assign( TextureLoader.prototype, {

	load: function ( url, onLoad, onProgress, onError ) {

		var texture = new Texture();

		var loader = new ImageLoader( this.manager );
		loader.setCrossOrigin( this.crossOrigin );
		loader.setPath( this.path );
		loader.load( url, function ( image ) {

			// JPEGs can't have an alpha channel, so memory can be saved by storing them as RGB.
			var isJPEG = url.search( /\.(jpg|jpeg)$/ ) > 0 || url.search( /^data\:image\/jpeg/ ) === 0;

			texture.format = isJPEG ? RGBFormat : RGBAFormat;
			texture.image = image;
			texture.needsUpdate = true;

			if ( onLoad !== undefined ) {

				onLoad( texture );

			}

		}, onProgress, onError );

		return texture;

	},

	setCrossOrigin: function ( value ) {

		this.crossOrigin = value;
		return this;

	},

	setPath: function ( value ) {

		this.path = value;
		return this;

	}

} );

/**
 * @author mrdoob / http://mrdoob.com/
 * @author bhouston / http://clara.io/
 * @author stephomi / http://stephaneginier.com/
 */

function Raycaster( origin, direction, near, far ) {

	this.ray = new Ray( origin, direction );
	// direction is assumed to be normalized (for accurate distance calculations)

	this.near = near || 0;
	this.far = far || Infinity;

	this.params = {
		Mesh: {},
		Line: {},
		LOD: {},
		Points: { threshold: 1 },
		Sprite: {}
	};

	Object.defineProperties( this.params, {
		PointCloud: {
			get: function () {
				console.warn( 'THREE.Raycaster: params.PointCloud has been renamed to params.Points.' );
				return this.Points;
			}
		}
	} );

}

function ascSort( a, b ) {

	return a.distance - b.distance;

}

function intersectObject( object, raycaster, intersects, recursive ) {

	if ( object.visible === false ) return;

	object.raycast( raycaster, intersects );

	if ( recursive === true ) {

		var children = object.children;

		for ( var i = 0, l = children.length; i < l; i ++ ) {

			intersectObject( children[ i ], raycaster, intersects, true );

		}

	}

}

//

Raycaster.prototype = {

	constructor: Raycaster,

	linePrecision: 1,

	set: function ( origin, direction ) {

		// direction is assumed to be normalized (for accurate distance calculations)

		this.ray.set( origin, direction );

	},

	setFromCamera: function ( coords, camera ) {

		if ( (camera && camera.isPerspectiveCamera) ) {

			this.ray.origin.setFromMatrixPosition( camera.matrixWorld );
			this.ray.direction.set( coords.x, coords.y, 0.5 ).unproject( camera ).sub( this.ray.origin ).normalize();

		} else if ( (camera && camera.isOrthographicCamera) ) {

			this.ray.origin.set( coords.x, coords.y, ( camera.near + camera.far ) / ( camera.near - camera.far ) ).unproject( camera ); // set origin in plane of camera
			this.ray.direction.set( 0, 0, - 1 ).transformDirection( camera.matrixWorld );

		} else {

			console.error( 'THREE.Raycaster: Unsupported camera type.' );

		}

	},

	intersectObject: function ( object, recursive ) {

		var intersects = [];

		intersectObject( object, this, intersects, recursive );

		intersects.sort( ascSort );

		return intersects;

	},

	intersectObjects: function ( objects, recursive ) {

		var intersects = [];

		if ( Array.isArray( objects ) === false ) {

			console.warn( 'THREE.Raycaster.intersectObjects: objects is not an Array.' );
			return intersects;

		}

		for ( var i = 0, l = objects.length; i < l; i ++ ) {

			intersectObject( objects[ i ], this, intersects, recursive );

		}

		intersects.sort( ascSort );

		return intersects;

	}

};

/**
 * @author oosmoxiecode
 * @author mrdoob / http://mrdoob.com/
 * based on http://code.google.com/p/away3d/source/browse/trunk/fp10/Away3DLite/src/away3dlite/primitives/Torus.as?r=2888
 */

function TorusGeometry( radius, tube, radialSegments, tubularSegments, arc ) {

	Geometry.call( this );

	this.type = 'TorusGeometry';

	this.parameters = {
		radius: radius,
		tube: tube,
		radialSegments: radialSegments,
		tubularSegments: tubularSegments,
		arc: arc
	};

	this.fromBufferGeometry( new TorusBufferGeometry( radius, tube, radialSegments, tubularSegments, arc ) );

}

TorusGeometry.prototype = Object.create( Geometry.prototype );
TorusGeometry.prototype.constructor = TorusGeometry;

/**
 * @author Mugen87 / https://github.com/Mugen87
 */

function TorusBufferGeometry( radius, tube, radialSegments, tubularSegments, arc ) {

	BufferGeometry.call( this );

	this.type = 'TorusBufferGeometry';

	this.parameters = {
		radius: radius,
		tube: tube,
		radialSegments: radialSegments,
		tubularSegments: tubularSegments,
		arc: arc
	};

	radius = radius || 100;
	tube = tube || 40;
	radialSegments = Math.floor( radialSegments ) || 8;
	tubularSegments = Math.floor( tubularSegments ) || 6;
	arc = arc || Math.PI * 2;

	// buffers

	var indices = [];
	var vertices = [];
	var normals = [];
	var uvs = [];

	// helper variables

	var center = new Vector3();
	var vertex = new Vector3();
	var normal = new Vector3();

	var j, i;

	// generate vertices, normals and uvs

	for ( j = 0; j <= radialSegments; j ++ ) {

		for ( i = 0; i <= tubularSegments; i ++ ) {

			var u = i / tubularSegments * arc;
			var v = j / radialSegments * Math.PI * 2;

			// vertex

			vertex.x = ( radius + tube * Math.cos( v ) ) * Math.cos( u );
			vertex.y = ( radius + tube * Math.cos( v ) ) * Math.sin( u );
			vertex.z = tube * Math.sin( v );

			vertices.push( vertex.x, vertex.y, vertex.z );

			// normal

			center.x = radius * Math.cos( u );
			center.y = radius * Math.sin( u );
			normal.subVectors( vertex, center ).normalize();

			normals.push( normal.x, normal.y, normal.z );

			// uv

			uvs.push( i / tubularSegments );
			uvs.push( j / radialSegments );

		}

	}

	// generate indices

	for ( j = 1; j <= radialSegments; j ++ ) {

		for ( i = 1; i <= tubularSegments; i ++ ) {

			// indices

			var a = ( tubularSegments + 1 ) * j + i - 1;
			var b = ( tubularSegments + 1 ) * ( j - 1 ) + i - 1;
			var c = ( tubularSegments + 1 ) * ( j - 1 ) + i;
			var d = ( tubularSegments + 1 ) * j + i;

			// faces

			indices.push( a, b, d );
			indices.push( b, c, d );

		}

	}

	// build geometry

	this.setIndex( indices );
	this.addAttribute( 'position', new Float32BufferAttribute( vertices, 3 ) );
	this.addAttribute( 'normal', new Float32BufferAttribute( normals, 3 ) );
	this.addAttribute( 'uv', new Float32BufferAttribute( uvs, 2 ) );

}

TorusBufferGeometry.prototype = Object.create( BufferGeometry.prototype );
TorusBufferGeometry.prototype.constructor = TorusBufferGeometry;

/**
 * @author mrdoob / http://mrdoob.com/
 */

function SphereGeometry( radius, widthSegments, heightSegments, phiStart, phiLength, thetaStart, thetaLength ) {

	Geometry.call( this );

	this.type = 'SphereGeometry';

	this.parameters = {
		radius: radius,
		widthSegments: widthSegments,
		heightSegments: heightSegments,
		phiStart: phiStart,
		phiLength: phiLength,
		thetaStart: thetaStart,
		thetaLength: thetaLength
	};

	this.fromBufferGeometry( new SphereBufferGeometry( radius, widthSegments, heightSegments, phiStart, phiLength, thetaStart, thetaLength ) );

}

SphereGeometry.prototype = Object.create( Geometry.prototype );
SphereGeometry.prototype.constructor = SphereGeometry;

/**
 * @author benaadams / https://twitter.com/ben_a_adams
 * @author Mugen87 / https://github.com/Mugen87
 */

function SphereBufferGeometry( radius, widthSegments, heightSegments, phiStart, phiLength, thetaStart, thetaLength ) {

	BufferGeometry.call( this );

	this.type = 'SphereBufferGeometry';

	this.parameters = {
		radius: radius,
		widthSegments: widthSegments,
		heightSegments: heightSegments,
		phiStart: phiStart,
		phiLength: phiLength,
		thetaStart: thetaStart,
		thetaLength: thetaLength
	};

	radius = radius || 50;

	widthSegments = Math.max( 3, Math.floor( widthSegments ) || 8 );
	heightSegments = Math.max( 2, Math.floor( heightSegments ) || 6 );

	phiStart = phiStart !== undefined ? phiStart : 0;
	phiLength = phiLength !== undefined ? phiLength : Math.PI * 2;

	thetaStart = thetaStart !== undefined ? thetaStart : 0;
	thetaLength = thetaLength !== undefined ? thetaLength : Math.PI;

	var thetaEnd = thetaStart + thetaLength;

	var ix, iy;

	var index = 0;
	var grid = [];

	var vertex = new Vector3();
	var normal = new Vector3();

	// buffers

	var indices = [];
	var vertices = [];
	var normals = [];
	var uvs = [];

	// generate vertices, normals and uvs

	for ( iy = 0; iy <= heightSegments; iy ++ ) {

		var verticesRow = [];

		var v = iy / heightSegments;

		for ( ix = 0; ix <= widthSegments; ix ++ ) {

			var u = ix / widthSegments;

			// vertex

			vertex.x = - radius * Math.cos( phiStart + u * phiLength ) * Math.sin( thetaStart + v * thetaLength );
			vertex.y = radius * Math.cos( thetaStart + v * thetaLength );
			vertex.z = radius * Math.sin( phiStart + u * phiLength ) * Math.sin( thetaStart + v * thetaLength );

			vertices.push( vertex.x, vertex.y, vertex.z );

			// normal

			normal.set( vertex.x, vertex.y, vertex.z ).normalize();
			normals.push( normal.x, normal.y, normal.z );

			// uv

			uvs.push( u, 1 - v );

			verticesRow.push( index ++ );

		}

		grid.push( verticesRow );

	}

	// indices

	for ( iy = 0; iy < heightSegments; iy ++ ) {

		for ( ix = 0; ix < widthSegments; ix ++ ) {

			var a = grid[ iy ][ ix + 1 ];
			var b = grid[ iy ][ ix ];
			var c = grid[ iy + 1 ][ ix ];
			var d = grid[ iy + 1 ][ ix + 1 ];

			if ( iy !== 0 || thetaStart > 0 ) indices.push( a, b, d );
			if ( iy !== heightSegments - 1 || thetaEnd < Math.PI ) indices.push( b, c, d );

		}

	}

	// build geometry

	this.setIndex( indices );
	this.addAttribute( 'position', new Float32BufferAttribute( vertices, 3 ) );
	this.addAttribute( 'normal', new Float32BufferAttribute( normals, 3 ) );
	this.addAttribute( 'uv', new Float32BufferAttribute( uvs, 2 ) );

}

SphereBufferGeometry.prototype = Object.create( BufferGeometry.prototype );
SphereBufferGeometry.prototype.constructor = SphereBufferGeometry;

/**
 * @author hughes
 */

function CircleGeometry( radius, segments, thetaStart, thetaLength ) {

	Geometry.call( this );

	this.type = 'CircleGeometry';

	this.parameters = {
		radius: radius,
		segments: segments,
		thetaStart: thetaStart,
		thetaLength: thetaLength
	};

	this.fromBufferGeometry( new CircleBufferGeometry( radius, segments, thetaStart, thetaLength ) );

}

CircleGeometry.prototype = Object.create( Geometry.prototype );
CircleGeometry.prototype.constructor = CircleGeometry;

/**
 * @author benaadams / https://twitter.com/ben_a_adams
 * @author Mugen87 / https://github.com/Mugen87
 */

function CircleBufferGeometry( radius, segments, thetaStart, thetaLength ) {

	BufferGeometry.call( this );

	this.type = 'CircleBufferGeometry';

	this.parameters = {
		radius: radius,
		segments: segments,
		thetaStart: thetaStart,
		thetaLength: thetaLength
	};

	radius = radius || 50;
	segments = segments !== undefined ? Math.max( 3, segments ) : 8;

	thetaStart = thetaStart !== undefined ? thetaStart : 0;
	thetaLength = thetaLength !== undefined ? thetaLength : Math.PI * 2;

	// buffers

	var indices = [];
	var vertices = [];
	var normals = [];
	var uvs = [];

	// helper variables

	var i, s;
	var vertex = new Vector3();
	var uv = new Vector2();

	// center point

	vertices.push( 0, 0, 0 );
	normals.push( 0, 0, 1 );
	uvs.push( 0.5, 0.5 );

	for ( s = 0, i = 3; s <= segments; s ++, i += 3 ) {

		var segment = thetaStart + s / segments * thetaLength;

		// vertex

		vertex.x = radius * Math.cos( segment );
		vertex.y = radius * Math.sin( segment );

		vertices.push( vertex.x, vertex.y, vertex.z );

		// normal

		normals.push( 0, 0, 1 );

		// uvs

		uv.x = ( vertices[ i ] / radius + 1 ) / 2;
		uv.y = ( vertices[ i + 1 ] / radius + 1 ) / 2;

		uvs.push( uv.x, uv.y );

	}

	// indices

	for ( i = 1; i <= segments; i ++ ) {

		indices.push( i, i + 1, 0 );

	}

	// build geometry

	this.setIndex( indices );
	this.addAttribute( 'position', new Float32BufferAttribute( vertices, 3 ) );
	this.addAttribute( 'normal', new Float32BufferAttribute( normals, 3 ) );
	this.addAttribute( 'uv', new Float32BufferAttribute( uvs, 2 ) );

}

CircleBufferGeometry.prototype = Object.create( BufferGeometry.prototype );
CircleBufferGeometry.prototype.constructor = CircleBufferGeometry;

/**
 * @author Kaleb Murphy
 */

function RingGeometry( innerRadius, outerRadius, thetaSegments, phiSegments, thetaStart, thetaLength ) {

	Geometry.call( this );

	this.type = 'RingGeometry';

	this.parameters = {
		innerRadius: innerRadius,
		outerRadius: outerRadius,
		thetaSegments: thetaSegments,
		phiSegments: phiSegments,
		thetaStart: thetaStart,
		thetaLength: thetaLength
	};

	this.fromBufferGeometry( new RingBufferGeometry( innerRadius, outerRadius, thetaSegments, phiSegments, thetaStart, thetaLength ) );

}

RingGeometry.prototype = Object.create( Geometry.prototype );
RingGeometry.prototype.constructor = RingGeometry;

/**
 * @author Mugen87 / https://github.com/Mugen87
 */

function RingBufferGeometry( innerRadius, outerRadius, thetaSegments, phiSegments, thetaStart, thetaLength ) {

	BufferGeometry.call( this );

	this.type = 'RingBufferGeometry';

	this.parameters = {
		innerRadius: innerRadius,
		outerRadius: outerRadius,
		thetaSegments: thetaSegments,
		phiSegments: phiSegments,
		thetaStart: thetaStart,
		thetaLength: thetaLength
	};

	innerRadius = innerRadius || 20;
	outerRadius = outerRadius || 50;

	thetaStart = thetaStart !== undefined ? thetaStart : 0;
	thetaLength = thetaLength !== undefined ? thetaLength : Math.PI * 2;

	thetaSegments = thetaSegments !== undefined ? Math.max( 3, thetaSegments ) : 8;
	phiSegments = phiSegments !== undefined ? Math.max( 1, phiSegments ) : 1;

	// buffers

	var indices = [];
	var vertices = [];
	var normals = [];
	var uvs = [];

	// some helper variables

	var segment;
	var radius = innerRadius;
	var radiusStep = ( ( outerRadius - innerRadius ) / phiSegments );
	var vertex = new Vector3();
	var uv = new Vector2();
	var j, i;

	// generate vertices, normals and uvs

	for ( j = 0; j <= phiSegments; j ++ ) {

		for ( i = 0; i <= thetaSegments; i ++ ) {

			// values are generate from the inside of the ring to the outside

			segment = thetaStart + i / thetaSegments * thetaLength;

			// vertex

			vertex.x = radius * Math.cos( segment );
			vertex.y = radius * Math.sin( segment );

			vertices.push( vertex.x, vertex.y, vertex.z );

			// normal

			normals.push( 0, 0, 1 );

			// uv

			uv.x = ( vertex.x / outerRadius + 1 ) / 2;
			uv.y = ( vertex.y / outerRadius + 1 ) / 2;

			uvs.push( uv.x, uv.y );

		}

		// increase the radius for next row of vertices

		radius += radiusStep;

	}

	// indices

	for ( j = 0; j < phiSegments; j ++ ) {

		var thetaSegmentLevel = j * ( thetaSegments + 1 );

		for ( i = 0; i < thetaSegments; i ++ ) {

			segment = i + thetaSegmentLevel;

			var a = segment;
			var b = segment + thetaSegments + 1;
			var c = segment + thetaSegments + 2;
			var d = segment + 1;

			// faces

			indices.push( a, b, d );
			indices.push( b, c, d );

		}

	}

	// build geometry

	this.setIndex( indices );
	this.addAttribute( 'position', new Float32BufferAttribute( vertices, 3 ) );
	this.addAttribute( 'normal', new Float32BufferAttribute( normals, 3 ) );
	this.addAttribute( 'uv', new Float32BufferAttribute( uvs, 2 ) );

}

RingBufferGeometry.prototype = Object.create( BufferGeometry.prototype );
RingBufferGeometry.prototype.constructor = RingBufferGeometry;

/**
 * @author dmarcos / https://github.com/dmarcos
 * @author mrdoob / http://mrdoob.com
 */

THREE.VRControls = function ( object, onError ) {

	var scope = this;

	var vrDisplay, vrDisplays;

	var standingMatrix = new THREE.Matrix4();

	var frameData = null;

	if ( 'VRFrameData' in window ) {

		frameData = new VRFrameData();

	}

	function gotVRDisplays( displays ) {

		vrDisplays = displays;

		if ( displays.length > 0 ) {

			vrDisplay = displays[ 0 ];

		} else {

			if ( onError ) onError( 'VR input not available.' );

		}

	}

	if ( navigator.getVRDisplays ) {

		navigator.getVRDisplays().then( gotVRDisplays ).catch ( function () {

			console.warn( 'THREE.VRControls: Unable to get VR Displays' );

		} );

	}

	// the Rift SDK returns the position in meters
	// this scale factor allows the user to define how meters
	// are converted to scene units.

	this.scale = 1;

	// If true will use "standing space" coordinate system where y=0 is the
	// floor and x=0, z=0 is the center of the room.
	this.standing = false;

	// Distance from the users eyes to the floor in meters. Used when
	// standing=true but the VRDisplay doesn't provide stageParameters.
	this.userHeight = 1.6;

	this.getVRDisplay = function () {

		return vrDisplay;

	};

	this.setVRDisplay = function ( value ) {

		vrDisplay = value;

	};

	this.getVRDisplays = function () {

		console.warn( 'THREE.VRControls: getVRDisplays() is being deprecated.' );
		return vrDisplays;

	};

	this.getStandingMatrix = function () {

		return standingMatrix;

	};

	this.update = function () {

		if ( vrDisplay ) {

			var pose;

			if ( vrDisplay.getFrameData ) {

				vrDisplay.getFrameData( frameData );
				pose = frameData.pose;

			} else if ( vrDisplay.getPose ) {

				pose = vrDisplay.getPose();

			}

			if ( pose.orientation !== null ) {

				object.quaternion.fromArray( pose.orientation );

			}

			if ( pose.position !== null ) {

				object.position.fromArray( pose.position );

			} else {

				object.position.set( 0, 0, 0 );

			}

			if ( this.standing ) {

				if ( vrDisplay.stageParameters ) {

					object.updateMatrix();

					standingMatrix.fromArray( vrDisplay.stageParameters.sittingToStandingTransform );
					object.applyMatrix( standingMatrix );

				} else {

					object.position.setY( object.position.y + this.userHeight );

				}

			}

			object.position.multiplyScalar( scope.scale );

		}

	};

	this.resetPose = function () {

		if ( vrDisplay ) {

			vrDisplay.resetPose();

		}

	};

	this.resetSensor = function () {

		console.warn( 'THREE.VRControls: .resetSensor() is now .resetPose().' );
		this.resetPose();

	};

	this.zeroSensor = function () {

		console.warn( 'THREE.VRControls: .zeroSensor() is now .resetPose().' );
		this.resetPose();

	};

	this.dispose = function () {

		vrDisplay = null;

	};

};

/**
 * @author dmarcos / https://github.com/dmarcos
 * @author mrdoob / http://mrdoob.com
 *
 * WebVR Spec: http://mozvr.github.io/webvr-spec/webvr.html
 *
 * Firefox: http://mozvr.com/downloads/
 * Chromium: https://webvr.info/get-chrome
 *
 */

THREE.VREffect = function( renderer, onError ) {

	var vrDisplay, vrDisplays;
	var eyeTranslationL = new THREE.Vector3();
	var eyeTranslationR = new THREE.Vector3();
	var renderRectL, renderRectR;

	var frameData = null;

	if ( 'VRFrameData' in window ) {

		frameData = new window.VRFrameData();

	}

	function gotVRDisplays( displays ) {

		vrDisplays = displays;

		if ( displays.length > 0 ) {

			vrDisplay = displays[ 0 ];

		} else {

			if ( onError ) onError( 'HMD not available' );

		}

	}

	if ( navigator.getVRDisplays ) {

		navigator.getVRDisplays().then( gotVRDisplays ).catch( function() {

			console.warn( 'THREE.VREffect: Unable to get VR Displays' );

		} );

	}

	//

	this.isPresenting = false;
	this.scale = 1;

	var scope = this;

	var rendererSize = renderer.getSize();
	var rendererUpdateStyle = false;
	var rendererPixelRatio = renderer.getPixelRatio();

	this.getVRDisplay = function() {

		return vrDisplay;

	};

	this.setVRDisplay = function( value ) {

		vrDisplay = value;

	};

	this.getVRDisplays = function() {

		console.warn( 'THREE.VREffect: getVRDisplays() is being deprecated.' );
		return vrDisplays;

	};

	this.setSize = function( width, height, updateStyle ) {

		rendererSize = { width: width, height: height };
		rendererUpdateStyle = updateStyle;

		if ( scope.isPresenting ) {

			var eyeParamsL = vrDisplay.getEyeParameters( 'left' );
			renderer.setPixelRatio( 1 );
			renderer.setSize( eyeParamsL.renderWidth * 2, eyeParamsL.renderHeight, false );

		} else {

			renderer.setPixelRatio( rendererPixelRatio );
			renderer.setSize( width, height, updateStyle );

		}

	};

	// VR presentation

	var canvas = renderer.domElement;
	var defaultLeftBounds = [ 0.0, 0.0, 0.5, 1.0 ];
	var defaultRightBounds = [ 0.5, 0.0, 0.5, 1.0 ];

	function onVRDisplayPresentChange() {

		var wasPresenting = scope.isPresenting;
		scope.isPresenting = vrDisplay !== undefined && vrDisplay.isPresenting;

		if ( scope.isPresenting ) {

			var eyeParamsL = vrDisplay.getEyeParameters( 'left' );
			var eyeWidth = eyeParamsL.renderWidth;
			var eyeHeight = eyeParamsL.renderHeight;

			if ( ! wasPresenting ) {

				rendererPixelRatio = renderer.getPixelRatio();
				rendererSize = renderer.getSize();

				renderer.setPixelRatio( 1 );
				renderer.setSize( eyeWidth * 2, eyeHeight, false );

			}

		} else if ( wasPresenting ) {

			renderer.setPixelRatio( rendererPixelRatio );
			renderer.setSize( rendererSize.width, rendererSize.height, rendererUpdateStyle );

		}

	}

	window.addEventListener( 'vrdisplaypresentchange', onVRDisplayPresentChange, false );

	this.setFullScreen = function( boolean ) {

		return new Promise( function( resolve, reject ) {

			if ( vrDisplay === undefined ) {

				reject( new Error( 'No VR hardware found.' ) );
				return;

			}

			if ( scope.isPresenting === boolean ) {

				resolve();
				return;

			}

			if ( boolean ) {

				resolve( vrDisplay.requestPresent( [ { source: canvas } ] ) );

			} else {

				resolve( vrDisplay.exitPresent() );

			}

		} );

	};

	this.requestPresent = function() {

		return this.setFullScreen( true );

	};

	this.exitPresent = function() {

		return this.setFullScreen( false );

	};

	this.requestAnimationFrame = function( f ) {

		if ( vrDisplay !== undefined ) {

			return vrDisplay.requestAnimationFrame( f );

		} else {

			return window.requestAnimationFrame( f );

		}

	};

	this.cancelAnimationFrame = function( h ) {

		if ( vrDisplay !== undefined ) {

			vrDisplay.cancelAnimationFrame( h );

		} else {

			window.cancelAnimationFrame( h );

		}

	};

	this.submitFrame = function() {

		if ( vrDisplay !== undefined && scope.isPresenting ) {

			vrDisplay.submitFrame();

		}

	};

	this.autoSubmitFrame = true;

	// render

	var cameraL = new THREE.PerspectiveCamera();
	cameraL.layers.enable( 1 );

	var cameraR = new THREE.PerspectiveCamera();
	cameraR.layers.enable( 2 );

	this.render = function( scene, camera, renderTarget, forceClear ) {

		if ( vrDisplay && scope.isPresenting ) {

			var autoUpdate = scene.autoUpdate;

			if ( autoUpdate ) {

				scene.updateMatrixWorld();
				scene.autoUpdate = false;

			}

			var eyeParamsL = vrDisplay.getEyeParameters( 'left' );
			var eyeParamsR = vrDisplay.getEyeParameters( 'right' );

			eyeTranslationL.fromArray( eyeParamsL.offset );
			eyeTranslationR.fromArray( eyeParamsR.offset );

			if ( Array.isArray( scene ) ) {

				console.warn( 'THREE.VREffect.render() no longer supports arrays. Use object.layers instead.' );
				scene = scene[ 0 ];

			}

			// When rendering we don't care what the recommended size is, only what the actual size
			// of the backbuffer is.
			var size = renderer.getSize();
			var layers = vrDisplay.getLayers();
			var leftBounds;
			var rightBounds;

			if ( layers.length ) {

				var layer = layers[ 0 ];

				leftBounds = layer.leftBounds !== null && layer.leftBounds.length === 4 ? layer.leftBounds : defaultLeftBounds;
				rightBounds = layer.rightBounds !== null && layer.rightBounds.length === 4 ? layer.rightBounds : defaultRightBounds;

			} else {

				leftBounds = defaultLeftBounds;
				rightBounds = defaultRightBounds;

			}

			renderRectL = {
				x: Math.round( size.width * leftBounds[ 0 ] ),
				y: Math.round( size.height * leftBounds[ 1 ] ),
				width: Math.round( size.width * leftBounds[ 2 ] ),
				height: Math.round( size.height * leftBounds[ 3 ] )
			};
			renderRectR = {
				x: Math.round( size.width * rightBounds[ 0 ] ),
				y: Math.round( size.height * rightBounds[ 1 ] ),
				width: Math.round( size.width * rightBounds[ 2 ] ),
				height: Math.round( size.height * rightBounds[ 3 ] )
			};

			if ( renderTarget ) {

				renderer.setRenderTarget( renderTarget );
				renderTarget.scissorTest = true;

			} else {

				renderer.setRenderTarget( null );
				renderer.setScissorTest( true );

			}

			if ( renderer.autoClear || forceClear ) renderer.clear();

			if ( camera.parent === null ) camera.updateMatrixWorld();

			camera.matrixWorld.decompose( cameraL.position, cameraL.quaternion, cameraL.scale );
			camera.matrixWorld.decompose( cameraR.position, cameraR.quaternion, cameraR.scale );

			var scale = this.scale;
			cameraL.translateOnAxis( eyeTranslationL, scale );
			cameraR.translateOnAxis( eyeTranslationR, scale );

			if ( vrDisplay.getFrameData ) {

				vrDisplay.depthNear = camera.near;
				vrDisplay.depthFar = camera.far;

				vrDisplay.getFrameData( frameData );

				cameraL.projectionMatrix.elements = frameData.leftProjectionMatrix;
				cameraR.projectionMatrix.elements = frameData.rightProjectionMatrix;

			} else {

				cameraL.projectionMatrix = fovToProjection( eyeParamsL.fieldOfView, true, camera.near, camera.far );
				cameraR.projectionMatrix = fovToProjection( eyeParamsR.fieldOfView, true, camera.near, camera.far );

			}

			// render left eye
			if ( renderTarget ) {

				renderTarget.viewport.set( renderRectL.x, renderRectL.y, renderRectL.width, renderRectL.height );
				renderTarget.scissor.set( renderRectL.x, renderRectL.y, renderRectL.width, renderRectL.height );

			} else {

				renderer.setViewport( renderRectL.x, renderRectL.y, renderRectL.width, renderRectL.height );
				renderer.setScissor( renderRectL.x, renderRectL.y, renderRectL.width, renderRectL.height );

			}
			renderer.render( scene, cameraL, renderTarget, forceClear );

			// render right eye
			if ( renderTarget ) {

				renderTarget.viewport.set( renderRectR.x, renderRectR.y, renderRectR.width, renderRectR.height );
				renderTarget.scissor.set( renderRectR.x, renderRectR.y, renderRectR.width, renderRectR.height );

			} else {

				renderer.setViewport( renderRectR.x, renderRectR.y, renderRectR.width, renderRectR.height );
				renderer.setScissor( renderRectR.x, renderRectR.y, renderRectR.width, renderRectR.height );

			}
			renderer.render( scene, cameraR, renderTarget, forceClear );

			if ( renderTarget ) {

				renderTarget.viewport.set( 0, 0, size.width, size.height );
				renderTarget.scissor.set( 0, 0, size.width, size.height );
				renderTarget.scissorTest = false;
				renderer.setRenderTarget( null );

			} else {

				renderer.setViewport( 0, 0, size.width, size.height );
				renderer.setScissorTest( false );

			}

			if ( autoUpdate ) {

				scene.autoUpdate = true;

			}

			if ( scope.autoSubmitFrame ) {

				scope.submitFrame();

			}

			return;

		}

		// Regular render mode if not HMD

		renderer.render( scene, camera, renderTarget, forceClear );

	};

	this.dispose = function() {

		window.removeEventListener( 'vrdisplaypresentchange', onVRDisplayPresentChange, false );

	};

	//

	function fovToNDCScaleOffset( fov ) {

		var pxscale = 2.0 / ( fov.leftTan + fov.rightTan );
		var pxoffset = ( fov.leftTan - fov.rightTan ) * pxscale * 0.5;
		var pyscale = 2.0 / ( fov.upTan + fov.downTan );
		var pyoffset = ( fov.upTan - fov.downTan ) * pyscale * 0.5;
		return { scale: [ pxscale, pyscale ], offset: [ pxoffset, pyoffset ] };

	}

	function fovPortToProjection( fov, rightHanded, zNear, zFar ) {

		rightHanded = rightHanded === undefined ? true : rightHanded;
		zNear = zNear === undefined ? 0.01 : zNear;
		zFar = zFar === undefined ? 10000.0 : zFar;

		var handednessScale = rightHanded ? - 1.0 : 1.0;

		// start with an identity matrix
		var mobj = new THREE.Matrix4();
		var m = mobj.elements;

		// and with scale/offset info for normalized device coords
		var scaleAndOffset = fovToNDCScaleOffset( fov );

		// X result, map clip edges to [-w,+w]
		m[ 0 * 4 + 0 ] = scaleAndOffset.scale[ 0 ];
		m[ 0 * 4 + 1 ] = 0.0;
		m[ 0 * 4 + 2 ] = scaleAndOffset.offset[ 0 ] * handednessScale;
		m[ 0 * 4 + 3 ] = 0.0;

		// Y result, map clip edges to [-w,+w]
		// Y offset is negated because this proj matrix transforms from world coords with Y=up,
		// but the NDC scaling has Y=down (thanks D3D?)
		m[ 1 * 4 + 0 ] = 0.0;
		m[ 1 * 4 + 1 ] = scaleAndOffset.scale[ 1 ];
		m[ 1 * 4 + 2 ] = - scaleAndOffset.offset[ 1 ] * handednessScale;
		m[ 1 * 4 + 3 ] = 0.0;

		// Z result (up to the app)
		m[ 2 * 4 + 0 ] = 0.0;
		m[ 2 * 4 + 1 ] = 0.0;
		m[ 2 * 4 + 2 ] = zFar / ( zNear - zFar ) * - handednessScale;
		m[ 2 * 4 + 3 ] = ( zFar * zNear ) / ( zNear - zFar );

		// W result (= Z in)
		m[ 3 * 4 + 0 ] = 0.0;
		m[ 3 * 4 + 1 ] = 0.0;
		m[ 3 * 4 + 2 ] = handednessScale;
		m[ 3 * 4 + 3 ] = 0.0;

		mobj.transpose();

		return mobj;

	}

	function fovToProjection( fov, rightHanded, zNear, zFar ) {

		var DEG2RAD = Math.PI / 180.0;

		var fovPort = {
			upTan: Math.tan( fov.upDegrees * DEG2RAD ),
			downTan: Math.tan( fov.downDegrees * DEG2RAD ),
			leftTan: Math.tan( fov.leftDegrees * DEG2RAD ),
			rightTan: Math.tan( fov.rightDegrees * DEG2RAD )
		};

		return fovPortToProjection( fovPort, rightHanded, zNear, zFar );

	}

};

exports.WebGLRenderer = WebGLRenderer;
exports.Scene = Scene;
exports.Mesh = Mesh;
exports.VideoTexture = VideoTexture;
exports.MeshBasicMaterial = MeshBasicMaterial;
exports.ShaderMaterial = ShaderMaterial;
exports.TextureLoader = TextureLoader;
exports.PerspectiveCamera = PerspectiveCamera;
exports.Object3D = Object3D;
exports.Raycaster = Raycaster;
exports.Math = _Math;
exports.Quaternion = Quaternion;
exports.Euler = Euler;
exports.Matrix4 = Matrix4;
exports.Matrix3 = Matrix3;
exports.Vector4 = Vector4;
exports.Vector3 = Vector3;
exports.Vector2 = Vector2;
exports.Color = Color;
exports.TorusGeometry = TorusGeometry;
exports.SphereGeometry = SphereGeometry;
exports.CircleGeometry = CircleGeometry;
exports.RingGeometry = RingGeometry;
exports.REVISION = REVISION;
exports.MOUSE = MOUSE;
exports.CullFaceNone = CullFaceNone;
exports.CullFaceBack = CullFaceBack;
exports.CullFaceFront = CullFaceFront;
exports.CullFaceFrontBack = CullFaceFrontBack;
exports.FrontFaceDirectionCW = FrontFaceDirectionCW;
exports.FrontFaceDirectionCCW = FrontFaceDirectionCCW;
exports.BasicShadowMap = BasicShadowMap;
exports.PCFShadowMap = PCFShadowMap;
exports.PCFSoftShadowMap = PCFSoftShadowMap;
exports.FrontSide = FrontSide;
exports.BackSide = BackSide;
exports.DoubleSide = DoubleSide;
exports.FlatShading = FlatShading;
exports.SmoothShading = SmoothShading;
exports.NoColors = NoColors;
exports.FaceColors = FaceColors;
exports.VertexColors = VertexColors;
exports.NoBlending = NoBlending;
exports.NormalBlending = NormalBlending;
exports.AdditiveBlending = AdditiveBlending;
exports.SubtractiveBlending = SubtractiveBlending;
exports.MultiplyBlending = MultiplyBlending;
exports.CustomBlending = CustomBlending;
exports.AddEquation = AddEquation;
exports.SubtractEquation = SubtractEquation;
exports.ReverseSubtractEquation = ReverseSubtractEquation;
exports.MinEquation = MinEquation;
exports.MaxEquation = MaxEquation;
exports.ZeroFactor = ZeroFactor;
exports.OneFactor = OneFactor;
exports.SrcColorFactor = SrcColorFactor;
exports.OneMinusSrcColorFactor = OneMinusSrcColorFactor;
exports.SrcAlphaFactor = SrcAlphaFactor;
exports.OneMinusSrcAlphaFactor = OneMinusSrcAlphaFactor;
exports.DstAlphaFactor = DstAlphaFactor;
exports.OneMinusDstAlphaFactor = OneMinusDstAlphaFactor;
exports.DstColorFactor = DstColorFactor;
exports.OneMinusDstColorFactor = OneMinusDstColorFactor;
exports.SrcAlphaSaturateFactor = SrcAlphaSaturateFactor;
exports.NeverDepth = NeverDepth;
exports.AlwaysDepth = AlwaysDepth;
exports.LessDepth = LessDepth;
exports.LessEqualDepth = LessEqualDepth;
exports.EqualDepth = EqualDepth;
exports.GreaterEqualDepth = GreaterEqualDepth;
exports.GreaterDepth = GreaterDepth;
exports.NotEqualDepth = NotEqualDepth;
exports.MultiplyOperation = MultiplyOperation;
exports.MixOperation = MixOperation;
exports.AddOperation = AddOperation;
exports.NoToneMapping = NoToneMapping;
exports.LinearToneMapping = LinearToneMapping;
exports.ReinhardToneMapping = ReinhardToneMapping;
exports.Uncharted2ToneMapping = Uncharted2ToneMapping;
exports.CineonToneMapping = CineonToneMapping;
exports.UVMapping = UVMapping;
exports.CubeReflectionMapping = CubeReflectionMapping;
exports.CubeRefractionMapping = CubeRefractionMapping;
exports.EquirectangularReflectionMapping = EquirectangularReflectionMapping;
exports.EquirectangularRefractionMapping = EquirectangularRefractionMapping;
exports.SphericalReflectionMapping = SphericalReflectionMapping;
exports.CubeUVReflectionMapping = CubeUVReflectionMapping;
exports.CubeUVRefractionMapping = CubeUVRefractionMapping;
exports.RepeatWrapping = RepeatWrapping;
exports.ClampToEdgeWrapping = ClampToEdgeWrapping;
exports.MirroredRepeatWrapping = MirroredRepeatWrapping;
exports.NearestFilter = NearestFilter;
exports.NearestMipMapNearestFilter = NearestMipMapNearestFilter;
exports.NearestMipMapLinearFilter = NearestMipMapLinearFilter;
exports.LinearFilter = LinearFilter;
exports.LinearMipMapNearestFilter = LinearMipMapNearestFilter;
exports.LinearMipMapLinearFilter = LinearMipMapLinearFilter;
exports.UnsignedByteType = UnsignedByteType;
exports.ByteType = ByteType;
exports.ShortType = ShortType;
exports.UnsignedShortType = UnsignedShortType;
exports.IntType = IntType;
exports.UnsignedIntType = UnsignedIntType;
exports.FloatType = FloatType;
exports.HalfFloatType = HalfFloatType;
exports.UnsignedShort4444Type = UnsignedShort4444Type;
exports.UnsignedShort5551Type = UnsignedShort5551Type;
exports.UnsignedShort565Type = UnsignedShort565Type;
exports.UnsignedInt248Type = UnsignedInt248Type;
exports.AlphaFormat = AlphaFormat;
exports.RGBFormat = RGBFormat;
exports.RGBAFormat = RGBAFormat;
exports.LuminanceFormat = LuminanceFormat;
exports.LuminanceAlphaFormat = LuminanceAlphaFormat;
exports.RGBEFormat = RGBEFormat;
exports.DepthFormat = DepthFormat;
exports.DepthStencilFormat = DepthStencilFormat;
exports.RGB_S3TC_DXT1_Format = RGB_S3TC_DXT1_Format;
exports.RGBA_S3TC_DXT1_Format = RGBA_S3TC_DXT1_Format;
exports.RGBA_S3TC_DXT3_Format = RGBA_S3TC_DXT3_Format;
exports.RGBA_S3TC_DXT5_Format = RGBA_S3TC_DXT5_Format;
exports.RGB_PVRTC_4BPPV1_Format = RGB_PVRTC_4BPPV1_Format;
exports.RGB_PVRTC_2BPPV1_Format = RGB_PVRTC_2BPPV1_Format;
exports.RGBA_PVRTC_4BPPV1_Format = RGBA_PVRTC_4BPPV1_Format;
exports.RGBA_PVRTC_2BPPV1_Format = RGBA_PVRTC_2BPPV1_Format;
exports.RGB_ETC1_Format = RGB_ETC1_Format;
exports.LoopOnce = LoopOnce;
exports.LoopRepeat = LoopRepeat;
exports.LoopPingPong = LoopPingPong;
exports.InterpolateDiscrete = InterpolateDiscrete;
exports.InterpolateLinear = InterpolateLinear;
exports.InterpolateSmooth = InterpolateSmooth;
exports.ZeroCurvatureEnding = ZeroCurvatureEnding;
exports.ZeroSlopeEnding = ZeroSlopeEnding;
exports.WrapAroundEnding = WrapAroundEnding;
exports.TrianglesDrawMode = TrianglesDrawMode;
exports.TriangleStripDrawMode = TriangleStripDrawMode;
exports.TriangleFanDrawMode = TriangleFanDrawMode;
exports.LinearEncoding = LinearEncoding;
exports.sRGBEncoding = sRGBEncoding;
exports.GammaEncoding = GammaEncoding;
exports.RGBEEncoding = RGBEEncoding;
exports.LogLuvEncoding = LogLuvEncoding;
exports.RGBM7Encoding = RGBM7Encoding;
exports.RGBM16Encoding = RGBM16Encoding;
exports.RGBDEncoding = RGBDEncoding;
exports.BasicDepthPacking = BasicDepthPacking;
exports.RGBADepthPacking = RGBADepthPacking;

Object.defineProperty(exports, '__esModule', { value: true });


Object.defineProperty( exports, 'AudioContext', {
	get: function () {
		return exports.getAudioContext();
	}
});

})));
