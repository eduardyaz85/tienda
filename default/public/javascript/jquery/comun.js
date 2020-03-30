/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */


function ObtenerDatos(datos) {
    var ite;
    var coleccion = [];
    for (var i = 0, max = datos.length; i < max; i++) {
        ite = datos[i]._data;
        coleccion[i] = ite;
    }
    return JSON.stringify(coleccion);
}
