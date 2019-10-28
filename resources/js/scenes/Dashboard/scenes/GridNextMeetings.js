import React, { Component } from 'react';
import { AgGridReact } from 'ag-grid-react';
import 'ag-grid-community/dist/styles/ag-grid.css';
import 'ag-grid-community/dist/styles/ag-theme-balham.css';
import 'ag-grid-community/dist/styles/ag-theme-material.css';
import agGridFavoriteCellRender from "../../../components/agGridFavoriteCellRender";
import agGridInitialCellRender from "../../../components/agGridInitialCellRender";
import agGridNameCellRender from "../../../components/agGridNameCellRender";
import agGridActionCellRender from "../../../components/agGridActionCellRender";

class GridNextMeetings extends Component {
        constructor(props) {
            super(props);
            var dateNow = new Date();
            var date = new Date();
            var dateLimit =  new Date(date.setMonth(date.getMonth()+1));
            const nextMeetings = [];
            props.jmaker.map(function(item) {
                if(item.meeting_date)
                {
                    if(new Date(item.meeting_date) > dateNow && new Date(item.meeting_date) <= dateLimit ){
                        nextMeetings.push(item)
                    }
                }
            });
            this.state = {
                columnDefs: props.column,
                rowData:nextMeetings,
                domLayout: "autoHeight",
                getRowNodeId: function(data) {
                    return data.uuid
                }
            }
            this.props.setRowData(nextMeetings.length)
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
                        overlayNoRowsTemplate={"<div><img class='img-grid' src='/_partnerV2/images/meeting_date.svg'/><p class='label-img'>"+t('no meetings')+"</p></div>"}
                        context={{ componentParent: this }}
                        frameworkComponents= {{
                            agGridFavoriteCellRender : agGridFavoriteCellRender,
                            agGridInitialCellRender : agGridInitialCellRender,
                            agGridNameCellRender : agGridNameCellRender,
                            agGridActionCellRender : agGridActionCellRender,
                        }}
                        onRowSelected={this.props.onRowSelected}
                        onRowClicked={this.props.onRowClicked}
                        onGridSizeChanged={this.props.onFirstDataRendered.bind(this)}
                        onFirstDataRendered={this.props.onFirstDataRendered.bind(this)}
                        getRowNodeId={this.state.getRowNodeId}

                    >
                    </AgGridReact>
                </div>
            </div>

        );
    }
}

export default GridNextMeetings;
