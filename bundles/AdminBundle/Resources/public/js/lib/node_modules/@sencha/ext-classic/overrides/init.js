// @override Ext

// This file is order extremely early (typically right after Ext.js) due to the
// above Cmd directive. This ensures that the "modern" and "classic" platform tags
// are properly set up as soon as possible.

Ext.platformTags.modern = !(Ext.platformTags.classic = Ext.isClassic = true);
