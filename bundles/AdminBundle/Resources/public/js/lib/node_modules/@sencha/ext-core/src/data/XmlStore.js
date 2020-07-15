/**
 * Small helper class to make creating {@link Ext.data.Store}s from XML data easier.
 * An XmlStore will be automatically configured with a {@link Ext.data.reader.Xml}.
 *
 * A store configuration would be something like:
 *
 *     var store = new Ext.data.XmlStore({
 *         // store configs
 *         storeId: 'myStore',
 *         url: 'sheldon.xml', // automatically configures a HttpProxy
 *
 *         // reader configs
 *         record: 'Item', // records will have an "Item" tag
 *         idPath: 'ASIN',
 *         totalRecords: '@TotalResults'
 *
 *         fields: [
 *             // set up the fields mapping into the xml doc
 *             // The first needs mapping, the others are very basic
 *             {name: 'Author', mapping: 'ItemAttributes > Author'},
 *             'Title', 'Manufacturer', 'ProductGroup'
 *         ]
 *     });
 *
 * This store is configured to consume a returned object of the form:
 *
 *      <?xml version="1.0" encoding="UTF-8"?>
 *      <ItemSearchResponse xmlns="http://webservices.amazon.com/AWSECommerceService/2009-05-15">
 *          <Items>
 *              <Request>
 *                  <IsValid>True</IsValid>
 *                  <ItemSearchRequest>
 *                      <Author>Sidney Sheldon</Author>
 *                      <SearchIndex>Books</SearchIndex>
 *                  </ItemSearchRequest>
 *              </Request>
 *              <TotalResults>203</TotalResults>
 *              <TotalPages>21</TotalPages>
 *              <Item>
 *                  <ASIN>0446355453</ASIN>
 *                  <DetailPageURL>
 *                      http://www.amazon.com/
 *                  </DetailPageURL>
 *                  <ItemAttributes>
 *                      <Author>Sidney Sheldon</Author>
 *                      <Manufacturer>Warner Books</Manufacturer>
 *                      <ProductGroup>Book</ProductGroup>
 *                      <Title>Master of the Game</Title>
 *                  </ItemAttributes>
 *              </Item>
 *          </Items>
 *      </ItemSearchResponse>
 *
 * An object literal of this form could also be used as the {@link #cfg-data} config option.
 * **Note:** This class accepts all of the configuration options of
 * {@link Ext.data.reader.Xml XmlReader}.
 */
Ext.define('Ext.data.XmlStore', {
    extend: 'Ext.data.Store',
    alias: 'store.xml',

    requires: [
        'Ext.data.proxy.Ajax',
        'Ext.data.reader.Xml',
        'Ext.data.writer.Xml'
    ],

    constructor: function(config) {
        config = Ext.apply({
            proxy: {
                type: 'ajax',
                reader: 'xml',
                writer: 'xml'
            }
        }, config);

        this.callParent([config]);
    }
});
