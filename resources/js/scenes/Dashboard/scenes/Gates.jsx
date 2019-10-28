import React, {Component} from 'react';
import 'ag-grid-community/dist/styles/ag-grid.css';
import 'ag-grid-community/dist/styles/ag-theme-balham.css';
import PropTypes from 'prop-types';
import AppBar from '@material-ui/core/AppBar';
import Tabs from '@material-ui/core/Tabs';
import Tab from '@material-ui/core/Tab';
import Typography from '@material-ui/core/Typography';
import GridLastInvitations from './GridLastInvitations';
import GridLastJmakerActif from './GridLastJmakerActif';
import GridInvitedUncommited from './GridInvitedUncommited';
import GridCommited from './GridCommited';
import GridNextMeetings from './GridNextMeetings';
import GridSharedSynthesis from './GridSharedSynthesis';
import agGridFavoriteCellRender from '../../../components/agGridFavoriteCellRender'
import agGridInitialCellRender from '../../../components/agGridInitialCellRender'
import agGridNameCellRender from '../../../components/agGridNameCellRender'
import ButtonBar from "../../../components/actionButtonBar";
import JmakerDetails from "./JmakerDetails";
import {SnackbarProvider} from 'notistack';
import withStyles from '@material-ui/core/styles/withStyles'
import styles from './styles'
import './../../../i18n';

function TabContainer(props) {
    return (
        <Typography component="div">
            {props.children}
        </Typography>
    );
}

TabContainer.propTypes = {
    children: PropTypes.node.isRequired,
};

function ExportColumn(tabValue,t,locale){
    return [
        {
            headerName: "logo", field: "", checkboxSelection: true, sortable: true, filter: true,width:50,cellStyle: {textAlign: 'right'},
            //cellRenderer: 'agGridInitialCellRender', logo avec initial
        },
        {
        headerName: "Name", field: "name", sortable: true, filter: true,width:180,cellStyle: {textAlign: 'left',marginLeft:'-3%',fontWeight:'normal',fontSize:'19px'},
            cellRenderer: 'agGridNameCellRender',
    },
   {
        headerName: "Inviter le", field: "created_at", sortable: true, filter: true,width:100,
        cellRenderer: (data) => {
            return data.value ? '<div style="font-size:12px;color:#616770;margin-top:3%">'+t("invited on")+': </div><div style="margin-top:-14%;margin-left:10%">'+(new Date(data.value)).toLocaleDateString(locale)+
                '</div>' : '';
        }
    },  {
        headerName: "Debrief le", field: "meeting_date", sortable: true, filter: true,width:100,
        cellRenderer: (data) => {
            return data.value ? '<div style="font-size:12px;color:#616770;margin-top:3%">'+t("debrief on")+': </div><div style="margin-top:-14%;margin-left:10%">'+(new Date(data.value)).toLocaleDateString(locale)+
                '</div>' : '';
        }
    }, {
        headerName: "Activé", field: "state", sortable: true, filter: true,width:80,
        cellRenderer: 'agGridFavoriteCellRender',
    },{
        headerName: "Atelier", field: "missions_ct", sortable: true, filter: true,width:80,
            cellRenderer: (data) => {
                return data.value ? '<div style="font-size:12px;color:#616770;margin-top:3%">'+t("mission number")+': </div><div style="margin-top:-20%;margin-left:10%">'+data.value+'</div>' : '';
            }
    }, {
        headerName: "Derniere activité", field: "last_page_at", sortable: true, filter: true,width:80,
            cellRenderer: (data) => {
                return data.value ? '<div style="font-size:12px;color:#616770;margin-top:3%">'+t("last connected")+': </div><div style="margin-top:-14%;margin-left:10%">'+(new Date(data.value)).toLocaleDateString(locale)+
                    '</div>' : '';
            }
    },{
        headerName: "Synthese partagée le", field: "synthesis_shared_at", sortable: true, filter: true,width:120,
            cellRenderer: (data) => {
                if(data.value){
                    var link = '/jmaker/rapport/'+data.value.substr(data.value.indexOf(':')+1);
                    var date = data.value.substr(0,data.value.indexOf(':'));
                }
                if(tabValue === 5){
                    return data.value ? '<div style="font-size:12px;color:#616770;margin-top:3%">'+t("synthesis shared on")+': </div><div style="margin-top:-9%;margin-left:10%">'+(new Date(date)).toLocaleDateString(locale)+
                        '<a href='+link+' target="blank"><img style="margin-left: 20%;" src="/images/svg/jobmakerFile.svg" width="20px" height="20px"></a></div>' : '';
                }else{
                    return data.value ? '<div style="font-size:12px;color:#616770;margin-top:3%">'+t("synthesis shared on")+': </div><div style="margin-top:-9%;margin-left:10%">'+(new Date(date)).toLocaleDateString(locale): '';
                }

            }
        }
    ];
}

class Gates extends Component {
    constructor(props) {
        super(props);
        this.state = {
          rowSelectedCount:0,
          value:0,
          showJmakerDetail: false,
          showGrid: true,
          selectedJmakers:[],
          showRelance:false,
          rowCount:"",
        };
    }

    updateCount(data){
        this.setState({
            ...this.state,
            rowSelectedCount:data
        })
    }

     handleChange(event, newValue){
         this.setState({value: newValue});
         this.setState({rowSelectedCount:0})
     }
    setRowData(data){
        this.setState({rowCount:data})
    }
    onRowSelected(event) {
        this.updateCount(event.api.getSelectedRows().length);
        this.setState({
            ...this.state,
            selectedJmakers: event.api.getSelectedRows()
        })
    }

    onRowClicked(e) {
        if (e.api.getFocusedCell().column.getColId() !== 'synthesis_shared_at') { // cell is from non-select column
            this.setState({
                ...this.state,
                showJmakerDetail: true,
                showGrid: false,
                selectedJmakers: e.data
            })
        }
    }

    deselectAll(){
        var grid = this.state.grid;
        grid.api.deselectAll()
    }
    onBackArrowClicked() {
        this.setState({
            ...this.state,
            showJmakerDetail: false,
            showGrid: true,
        })
        this.state.grid.api.refreshCells();
    }
    onGridReady(e){
        this.setState({
            ...this.state,
            grid: e,
        })
    }

    selectAll(e){
        var grid = this.state.grid;
        if(e.target.checked)
            grid.api.selectAll();
        else
            grid.api.deselectAll()
    }

    onFirstDataRendered(params) {
        params.api.sizeColumnsToFit();

    }

    handleClickRelance(data) {
        this.setState({
            ...this.state,
            showRelance: true,
        })
    }
    onUpdateJmaker(data){
        var updatedJmaker = this.state.selectedJmakers[0] ? this.state.selectedJmakers[0] : this.state.selectedJmakers;
        var rowNode = this.state.grid.api.getRowNode(updatedJmaker.uuid);
        var newRowNode = rowNode.data;
        if(data.relanceDate){
            updatedJmaker.recall_at = data.relanceDate;
            updatedJmaker.recall_ct = updatedJmaker.recall_ct + 1;
            this.setState({
                ...this.state,
                selectedJmakers: updatedJmaker,
            })
            newRowNode.recall_at = data.relanceDate;
            newRowNode.recall_ct = updatedJmaker.recall_ct;
        }
        else{
            updatedJmaker.meeting_date = data.meetingDate;
            this.setState({
                ...this.state,
                selectedJmakers: updatedJmaker,
            })
            newRowNode.meeting_date = data.meetingDate;
        }
        rowNode.setData(newRowNode);
    }
    render() {

        let {classes} = this.props
        let t = this.props.translate
        return (
            <div>

                    <SnackbarProvider anchorOrigin={{ vertical: 'top', horizontal: 'right'}} autoHideDuration={3000}>
                        {this.state.showJmakerDetail &&
                            <div>
                                <JmakerDetails onBackArrowClicked={this.onBackArrowClicked.bind(this)}
                                               jmaker={this.state.selectedJmakers}
                                               translate={t}
                                               onUpdateJmaker={this.onUpdateJmaker.bind(this)}/>
                            </div>
                        }
                        {this.state.showGrid &&
                            <div>
                                <AppBar position="static" color="inherit" className={classes.appBar}>
                                    <Tabs
                                        value={this.state.value}
                                        onChange={this.handleChange.bind(this)}
                                        indicatorColor="primary"
                                        textColor="primary"
                                        variant="standard"
                                        scrollButtons="auto"
                                    >
                                        <Tab className={classes.tab} label={t('last invitations')}/>
                                        <Tab className={classes.tab} label={t('last actif')}/>
                                        <Tab className={classes.tab} label={t('invited to remind')}/>
                                        <Tab className={classes.tab} label={t('involved to remind')}/>
                                        <Tab className={classes.tab} label={t('next meetings')}/>
                                        <Tab className={classes.tab} label={t('shared synthesis')}/>
                                    </Tabs >
                                    {this.state.rowCount !== 0 &&
                                        <ButtonBar rowCheckedCount={this.state.rowSelectedCount}
                                        jmakers={this.state.selectedJmakers}
                                        checkAll={true}
                                        selectAll={this.selectAll.bind(this)}
                                        deselectAll={this.deselectAll.bind(this)}
                                        translate={t}
                                        onUpdateJmaker={this.onUpdateJmaker.bind(this)}/>
                                    }
                                </AppBar>
                                <TabContainer
                                >
                                    {this.state.value === 0 &&
                                        <GridLastInvitations
                                            jmaker={this.props.jmakers}
                                            column={ExportColumn(this.state.value,t,this.props.locale)}
                                            updateCount={this.updateCount.bind(this)}
                                            tab={this.state.value}
                                            onRowSelected={this.onRowSelected.bind(this)}
                                            onRowClicked={this.onRowClicked.bind(this)}
                                            setRowData = {this.setRowData.bind(this)}
                                            onGridReady={this.onGridReady.bind(this)}
                                            onFirstDataRendered={this.onFirstDataRendered.bind(this)}
                                            translate={t}
                                        />
                                   }
                                    {this.state.value === 1 &&
                                        <GridLastJmakerActif
                                            id={'grid'+this.state.value}
                                            jmaker={this.props.jmakers}
                                            column={ExportColumn(this.state.value,t,this.props.locale)}
                                            updateCount={this.updateCount.bind(this)}
                                            tab={this.state.value}
                                            onRowSelected={this.onRowSelected.bind(this)}
                                            onRowClicked={this.onRowClicked.bind(this)}
                                            setRowData = {this.setRowData.bind(this)}
                                            onGridReady={this.onGridReady.bind(this)}
                                            onFirstDataRendered={this.onFirstDataRendered.bind(this)}
                                            translate={t}
                                        />
                                    }
                                    {this.state.value === 2 &&
                                        <GridInvitedUncommited
                                            jmaker={this.props.jmakers}
                                            column={ExportColumn(this.state.value,t,this.props.locale)}
                                            updateCount={this.updateCount.bind(this)}
                                            tab={this.state.value}
                                            onRowSelected={this.onRowSelected.bind(this)}
                                            onRowClicked={this.onRowClicked.bind(this)}
                                            setRowData = {this.setRowData.bind(this)}
                                            onGridReady={this.onGridReady.bind(this)}
                                            onFirstDataRendered={this.onFirstDataRendered.bind(this)}
                                            translate={t}
                                        />
                                    }
                                    {this.state.value === 3 &&
                                        <GridCommited
                                            jmaker={this.props.jmakers}
                                            column={ExportColumn(this.state.value,t,this.props.locale)}
                                            updateCount={this.updateCount.bind(this)}
                                            tab={this.state.value}
                                            onRowSelected={this.onRowSelected.bind(this)}
                                            onRowClicked={this.onRowClicked.bind(this)}
                                            setRowData = {this.setRowData.bind(this)}
                                            onGridReady={this.onGridReady.bind(this)}
                                            onFirstDataRendered={this.onFirstDataRendered.bind(this)}
                                            translate={t}
                                        />
                                    }
                                    {this.state.value === 4 &&
                                        <GridNextMeetings
                                            jmaker={this.props.jmakers}
                                            column={ExportColumn(this.state.value,t,this.props.locale)}
                                            updateCount={this.updateCount.bind(this)}
                                            tab={this.state.value}
                                            onRowSelected={this.onRowSelected.bind(this)}
                                            onRowClicked={this.onRowClicked.bind(this)}
                                            setRowData = {this.setRowData.bind(this)}
                                            onGridReady={this.onGridReady.bind(this)}
                                            onFirstDataRendered={this.onFirstDataRendered.bind(this)}
                                            translate={t}
                                        />
                                    }
                                    {this.state.value === 5 &&
                                        <GridSharedSynthesis
                                            jmaker={this.props.jmakers}
                                            column={ExportColumn(this.state.value,t,this.props.locale)}
                                            updateCount={this.updateCount.bind(this)}
                                            tab={this.state.value}
                                            onRowSelected={this.onRowSelected.bind(this)}
                                            onRowClicked={this.onRowClicked.bind(this)}
                                            setRowData = {this.setRowData.bind(this)}
                                            onGridReady={this.onGridReady.bind(this)}
                                            onFirstDataRendered={this.onFirstDataRendered.bind(this)}
                                            translate={t}
                                        />
                                     }
                                </TabContainer>
                            </div>
                        }
                    </SnackbarProvider>
            </div>
        );
    }
}
export default withStyles(styles)(Gates);
