import React, {Component} from 'react';
import Button from '@material-ui/core/Button';
import Dialog from '@material-ui/core/Dialog';
import DialogActions from '@material-ui/core/DialogActions';
import DialogContent from '@material-ui/core/DialogContent';
import DialogTitle from '@material-ui/core/DialogTitle';
import Avatar from "@material-ui/core/Avatar";
import {Form,Field} from 'react-final-form';
import gql from 'graphql-tag';
import { Mutation } from "react-apollo";
import { withSnackbar } from 'notistack';
import Chip from '@material-ui/core/Chip';
import styles from './styles';
import withStyles from '@material-ui/core/styles/withStyles';
import ApolloClient from "apollo-client";
import { InMemoryCache } from 'apollo-cache-inmemory';
import { HttpLink } from 'apollo-link-http';
import DateFnsUtils from '@date-io/date-fns';

import {MuiPickersUtilsProvider, KeyboardDatePicker} from '@material-ui/pickers';

const  SEND_MEETING_MUTATION = gql`
  mutation SendMeetingMutation($uuid:String!,$date:String!,$object:String!,$message:String) {
    sendMeeting(uuid: $uuid,date:$date,object:$object,message:$message) {
        uuid
        date
    }
  }
`;
const cache = new InMemoryCache();
const link = new HttpLink({
    uri:"https://partner."+env+"/graphql",
})

const client = new ApolloClient({
    cache,
    link,
    credentials: 'include'
})

class MeetingComponent extends Component{
    constructor(props) {
        super(props);
        this.state = {
            selectedDate:''
        }
    }

    componentWillReceiveProps(props) {
        this.setState({
            selectedDate: this.props.jmakers[0] ? this.props.jmakers[0].meeting_date :''
        })
    }

    onConfirm(){
        var t = this.props.translate
        this.props.enqueueSnackbar(this.props.jmakers[0].meeting_date ? t('confirm change meeting'):t('confirm set meeting'), {
            variant: 'success',
        })
        this.props.handleClose();
        this.props.onUpdateJmaker({'meetingDate':this.state.selectedDate});
    }
    onError(){
        var t = this.props.translate
        this.props.enqueueSnackbar(t('error'), {
            variant: 'error',
        })
    };
    handleDateChange(date, e) {
        this.setState({
            ...this.state,
            selectedDate:date,
        })
    };

    render() {
        let {classes} = this.props
        var t = this.props.translate
        var title = "";
        var initialFormData = '';
        if(this.props.jmakers[0]){
            title = null === this.props.jmakers[0].meeting_date  ? t('set meeting date'): t('change meeting date');
            initialFormData = {
                object: this.props.jmakers[0].language_id === "LANG_FR" ? "Votre prochain rendez vous avec votre RH": "Your next meeting with your HR",
                date: this.state.selectedDate,
                message: "",
            };
        }
        return (
            <div>
                {this.props.jmakers[0]  &&
                <Dialog open={this.props.open} onClose={this.props.handleClose} aria-labelledby="form-dialog-title" fullWidth={true}>
                    <DialogTitle  disableTypography={true}>
                        <Avatar aria-label="Recipe" style={{backgroundColor: '#39cfb4', float: 'right'}}>
                            {this.props.jmakers[0].name.substr(0, 1) + this.props.jmakers[0].name.substr(this.props.jmakers[0].name.indexOf(' ') + 1, 1)}
                        </Avatar>
                        <p style={{color: '#39cfb4', fontSize: '22px'}}>{title}</p>
                        <Chip  label={this.props.jmakers[0].name.substr(this.props.jmakers[0].name.indexOf('-') + 1)} />
                    </DialogTitle>
                    <Mutation mutation={SEND_MEETING_MUTATION} onCompleted={this.onConfirm.bind(this)} onError={this.onError.bind(this)}>
                        {(sendMeetingMutation) => (
                            <Form
                                onSubmit={values => {
                                    sendMeetingMutation({
                                                variables: {
                                                    uuid: this.props.jmakers[0].uuid,
                                                    object: values.object,
                                                    date: values.date,
                                                    message: values.message
                                                }
                                            })
                                }}
                                initialValues={initialFormData}
                                render={({handleSubmit}) => (
                                    <div>
                                        <form onSubmit={handleSubmit}>
                                            <DialogContent className={classes.relanceObject}>
                                                <label className={classes.label}>
                                                    {t('object')}
                                                    <Field
                                                        name="object"
                                                        type="text"
                                                        className={'form-control'}
                                                        component="input"
                                                        initialValue={initialFormData.object}
                                                        disabled
                                                        style={{fontSize: '13px'}}
                                                        required
                                                    >
                                                    </Field>
                                                </label>
                                            </DialogContent>
                                            <DialogContent className={classes.relanceObject}>
                                                <MuiPickersUtilsProvider utils={DateFnsUtils}>
                                                    <KeyboardDatePicker
                                                        disableToolbar
                                                        variant="inline"
                                                        format={this.props.jmakers[0].language_id === "LANG_FR" ? "dd/MM/yyyy":"MM/dd/yyyy"}
                                                        margin="normal"
                                                        label={t('debrief_date')}
                                                        className={classes.datepicker}
                                                        name={'meeting_date'}
                                                        InputProps={{
                                                            disableUnderline: true,
                                                        }}
                                                        autoOk
                                                        openTo="date"
                                                        value={initialFormData.date}
                                                        onChange={this.handleDateChange.bind(this)}
                                                        style={{fontSize: '13px'}}
                                                        invalidDateMessage={""}
                                                    />
                                                </MuiPickersUtilsProvider>
                                            </DialogContent>
                                            <DialogContent className={classes.relanceObject}>
                                                <label className={classes.label}>
                                                    Message
                                                <Field
                                                    name="message"
                                                    component="textarea"
                                                    type="text"
                                                    className={'form-control'}
                                                    style={{fontSize: '14px'}}
                                                    initialValue={initialFormData.message}
                                                />
                                                </label>
                                            </DialogContent>
                                            <DialogActions>
                                                <Button onClick={this.props.handleClose} >{t('cancel')}</Button>
                                                <Button type={"submit"}>{t('send')}</Button>
                                            </DialogActions>
                                        </form>
                                    </div>
                                )}
                            />
                        )}
                    </Mutation>
                </Dialog>
                }
            </div>
        );
    }
};
export default withSnackbar(withStyles(styles)(MeetingComponent));
