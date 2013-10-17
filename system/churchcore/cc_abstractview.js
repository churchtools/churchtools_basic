//=========== Abstract View ============================================//

// Dient als Konstrukturhilfe fuer die Vererbung von Objekten, ansonsten wuerde der Konstruktur aufgerufen werden.
// Dies ist eine Konzeptschwaeche in JS

	
var Temp = function() {};

function AbstractView() {
  this.name="AbstractView";
}
AbstractView.prototype.getName = function() {
  return this.name;
};
AbstractView.prototype.getData = function(sorted) {
  alert(this.name+".getData not implemented!");
};

AbstractView.prototype.renderMenu = function() {
  alert(this.name+".renderMenu not implemented!");
};

AbstractView.prototype.renderListMenu = function() {
  alert(this.name+".renderListMenu not implemented!");
};

AbstractView.prototype.getListHeader = function() {
  alert(this.name+".getListHeader not implemented!");
};

/*
 * Rendert einen einzelnen Eintrag in der Tabelle
 */
AbstractView.prototype.renderListEntry = function (entry) {
  alert(this.name+".renderListEntry not implemented!");
};

/*
 * Rendert die gesamte Tabelle und ruft jeweils renderEntry auf
 */
AbstractView.prototype.renderList = function() {
  alert(this.name+".renderList not implemented!");
};  
  
AbstractView.prototype.renderFilter = function() {
  alert(this.name+".renderFilter not implemented!");  
};

/**
 * 
 * @param id
 * @return True, wenn Datensatz angezeigt werden darf
 */
AbstractView.prototype.checkFilter = function(id) {
  alert(this.name+".checkFilter not implemented!");
};

/**
 * Normalerweise wird pos_id und data_id gleich gesetzt, wenn ich aber z.Bsp. eine Gruppenansicht bei der Person
 * anzeigen will, mache ich pos_id die p_id und data_id die g_id
 * @param pos_id = An welche Position soll in der Liste gerendert werden
 * @param data_id = Welcher Datensatz soll gerendert werden, wenn null, dann wird pos_id genommen!
 */
AbstractView.prototype.renderEntryDetail = function(pos_id, data_id) {
  alert(this.name+".renderEntryDetail not implemented!");
};

/**
 * Rendert das Formular, um neuen Datensatz anzulegen
 */
AbstractView.prototype.renderAddEntry = function() {
  alert(this.name+".renderAddEntry not implemented!");
};

/**
 * 
 * @param id Id des Datensatzes
 * @param fieldname Weitere Moeglichkeit das Editieren auf einzelne Datenfelder zu begrenzen
 */
AbstractView.prototype.renderEditEntry = function(id, fieldname) {
  alert(this.name+".renderEditEntry not implemented!");  
};
