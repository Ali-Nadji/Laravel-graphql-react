import React, { Component } from 'react';
import { AgGridReact } from 'ag-grid-react';
import 'ag-grid-community/dist/styles/ag-grid.css';
import 'ag-grid-community/dist/styles/ag-theme-balham.css';
import 'ag-grid-community/dist/styles/ag-theme-material.css';
import agGridFavoriteCellRender from '../../../components/agGridFavoriteCellRender'
import agGridInitialCellRender from "../../../components/agGridInitialCellRender";
import agGridNameCellRender from "../../../components/agGridNameCellRender";
import agGridActionCellRender from "../../../components/agGridActionCellRender";

class GridLastInvitations extends Component {
    constructor(props) {
        super(props);
        var dateNow = new Date();
        var month = dateNow.getMonth()+1;
        var date3month = new Date(dateNow.setMonth(month-4))
        const lastInvit = [];
        props.jmaker.map(function(item) {
            var jmakerDate = new Date(item.created_at)
            if(jmakerDate > date3month){
                lastInvit.push(item)
            }
        });

        this.state = {
            columnDefs: props.column,
            rowData: lastInvit,
            domLayout: "autoHeight",
            getRowNodeId: function(data) {
                return data.uuid
            }
        };
        this.props.setRowData(lastInvit.length)
    }

    render() {
        var t = this.props.translate

        return (
            <div style={{ backgroundColor:'#f4f8f9' }}>
                <div id="myGrid"
                     className="ag-theme-material"
                >
                    <AgGridReact
                        columnDefs={this.state.columnDefs}
                        rowData={this.state.rowData}
                        suppressCellSelection={true}
                        suppressMovableColumns={true}
                        sortable={true}
                        filter={true}
                        floatingFilter={true}
                        domLayout={this.state.domLayout}
                        pagination={true}
                        paginationAutoPageSize={false}
                        paginationPageSize={6}
                        colWidth={200}
                        rowHeight={90}
                        rowSelection='multiple'
                        suppressRowClickSelection={true}
                        onGridReady={this.props.onGridReady}
                        resizable={true}
                        headerHeight={38}
                        floatingFiltersHeight={38}
                        context={{ componentParent: this }}
                        overlayNoRowsTemplate={"<div><img class='img-grid' src='/_partnerV2/images/user_none_actif.svg'><p class='label-img'>"+t('no past invitations')+"</p></div>"}
                        frameworkComponents= {{
                            agGridFavoriteCellRender : agGridFavoriteCellRender,
                            agGridInitialCellRender : agGridInitialCellRender,
                            agGridNameCellRender : agGridNameCellRender,
                            agGridActionCellRender : agGridActionCellRender,
                        }}
                        onRowSelected={this.props.onRowSelected}
                        onRowClicked={this.props.onRowClicked}
                        onFirstDataRendered={this.props.onFirstDataRendered.bind(this)}
                        getRowNodeId={this.state.getRowNodeId}
                        onGridSizeChanged={this.props.onFirstDataRendered.bind(this)}
                    >
                    </AgGridReact>
                </div>
            </div>

        );
    }
}
export default GridLastInvitations;