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
import SaveIcon from '@material-ui/icons/Save';
import RestoreIcon from '@material-ui/icons/SettingsBackupRestore';
import styles from './styles';
import withStyles from '@material-ui/core/styles/withStyles';
import Tooltip from "@material-ui/core/Tooltip";
import ApolloClient from "apollo-client";
import { InMemoryCache } from 'apollo-cache-inmemory';
import { HttpLink } from 'apollo-link-http';

const SEND_RELANCE = gql`
  mutation SendRelance($uuid:[String]!,$objectFr:String,$messageFr:String,$objectEn:String,$messageEn:String) {
    sendRelance(uuid: $uuid,objectFr:$objectFr,messageFr:$messageFr,objectEn:$objectEn,messageEn:$messageEn) {
        uuid
    }
  }
`;

const SAVE_RELANCE_OBJ = gql`
  mutation SaveRelanceObj($objectFr:String,$objectEn:String,$messageFr:String,$messageEn:String) {
    saveRelanceObj(objectFr:$objectFr,objectEn:$objectEn,messageFr:$messageFr,messageEn:$messageEn) {
        uuid
        prescriber_uuid
        type
        data
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

class RelanceComponent extends Component{
    constructor(props) {
        super(props);
        this.state = {
            visibilitySaveMsgFr:false,
            visibilitySaveMsgEn:false,
            visibilityRestoreMsg:false,
            visibilitySaveObjFr: false,
            visibilitySaveObjEn: false,
            visibilityRestoreObj: false,
            customObjFr:'',
            customObjEn:'',
            customMsgFr:'',
            customMsgEn:'',
            jmakers:[]
        };
    }

    componentWillReceiveProps(props) {
        this.setState({jmakers:props.jmakers})
    }

    onConfirm(){
        const t = this.props.translate
        this.props.enqueueSnackbar(t('confirm relance'), {
            variant: 'success',
        })
        this.props.handleClose();
        var date = new Date();
        this.props.onUpdateJmaker({'relanceDate':date});
    };
    onError(){
        const t = this.props.translate
        this.props.enqueueSnackbar(t('error'), {
            variant: 'error',
        })
    };


    handleDelete(jmaker,index){
        var array = this.state.jmakers;
        if (index !== -1) {
            array.splice(index, 1);
            this.setState({jmakers: array});
        }
    }
    reInitialiseStates(){
        this.setState({
            visibilitySaveMsgFr:false,
            visibilitySaveMsgEn:false,
            visibilitySaveObjFr: false,
            visibilitySaveObjEn: false,
            visibilityRestoreObjFr: false,
            visibilityRestoreMsgFr:false,
            visibilityRestoreObjEn:false,
            visibilityRestoreMsgEn:false,
        })
        this.props.handleClose();
    }
    handleKeyUp(e){
        switch(true){
            case e.target.name === 'objectFr':
                this.setState({
                    ...this.state,
                    visibilitySaveObjFr:true,
                })
                break;
            case e.target.name === 'objectEn':
                this.setState({
                    ...this.state,
                    visibilitySaveObjEn:true,
                })
                break;
            case e.target.name === 'messageFr':
                this.setState({
                    ...this.state,
                    visibilitySaveMsgFr:true,
                })
                break;
            case e.target.name === 'messageEn':
                this.setState({
                    ...this.state,
                    visibilitySaveMsgEn:true,
                })
                break;
        }

    }

    resetField(values){
        if(values.restore.restoreObjFr === true) {
            this.setState({
                ...this.state,
                visibilityRestoreObjFr: false,
                customObjFr: "",
            })
        }
        else if(values.restore.restoreObjEn === true){
            this.setState({
                ...this.state,
                visibilityRestoreObjEn: false,
                customObjEn: "",
            })
        }

        else if(values.restore.restoreMsgFr === true) {
            this.setState({
                ...this.state,
                visibilityRestoreMsgFr: false,
                customMsgFr: "",
            })
        }
        else if(values.restore.restoreMsgEn === true) {
            this.setState({
                ...this.state,
                visibilityRestoreMsgEn: false,
                customMsgEn: "",
            })
        }
    }

    handleClickSave(values){
        const t = this.props.translate;
        client.mutate({
                mutation: SAVE_RELANCE_OBJ,
                variables: {
                    objectFr: values.objectFr,
                    messageFr: values.messageFr,
                    objectEn: values.objectEn,
                    messageEn: values.messageEn
                }
            }).then(() => {
                if(values.saving.saveObjFr === true){
                    this.setState({
                        ...this.state,
                        visibilitySaveObjFr: false,
                        visibilityRestoreObjFr:true,
                    })
                }
                if(values.saving.saveObjEn === true){
                    this.setState({
                        ...this.state,
                        visibilitySaveObjEn: false,
                        visibilityRestoreObjEn:true,
                    })
                }
               if(values.saving.saveMsgFr === true){
                    this.setState({
                        ...this.state,
                        visibilitySaveMsgFr:false,
                        visibilityRestoreMsgFr:true,
                    })
                }
                if(values.saving.saveMsgEn === true){
                    this.setState({
                        ...this.state,
                        visibilitySaveMsgEn:false,
                        visibilityRestoreMsgEn:true,
                    })
                }
                this.setState({
                    ...this.state,
                    customObjFr: values.objectFr ? values.objectFr :"" ,
                    customObjEn: values.objectEn ? values.objectEn : "",
                    customMsgFr: values.messageFr ? values.messageFr : "",
                    customMsgEn: values.messageEn ? values.messageEn : "" ,
                })

                values.saving.saveObjFr = false;
                values.saving.saveObjEn = false;
                values.saving.saveMsgEn = false;
                values.saving.saveMsgEn = false;
                this.props.enqueueSnackbar(t('save confirm'), {
                    variant: 'default',
                })
            }).catch(() => {
                this.onError()
            })
        }

    render() {
        let {classes} = this.props
        const t = this.props.translate
        var initialFormData = '';
        var jmakersUuid = [];
        var jmakersFr = [];
        var jmakersEn = [];
        var objectFr='';
        var objectEn='';
        var messageFr='';
        var messageEn='';

        if(this.props.jmakers.length > 0){
            this.props.jmakers.map(function (item,i) {
                jmakersUuid[i] = item.uuid;
                if(item.language_id === "LANG_EN"){
                    jmakersEn.push(item);
                    objectEn = item.default_relance_object_en;
                    messageEn = item.default_relance_message_en;
                }else if(item.language_id === "LANG_FR"){
                    jmakersFr.push(item);
                    objectFr = item.default_relance_object_fr;
                    messageFr = item.default_relance_message_fr;
                }
            })
        }

        let title = this.state.jmakers.length > 1 ? t('remind multiple'): t('remind');
        if(this.state.jmakers.length > 0) {
            initialFormData = {
                objectFr: this.state.customObjFr ? this.state.customObjFr : objectFr,
                messageFr: this.state.customMsgFr ? this.state.customMsgFr :messageFr,
                objectEn: this.state.customObjEn ? this.state.customObjEn :objectEn,
                messageEn: this.state.customMsgEn ? this.state.customMsgEn :messageEn,
            };
        }
        return (
            <div>
                {this.props.jmakers  &&
                <Dialog open={this.props.open} onClose={this.props.handleClose} aria-labelledby="form-dialog-title"
                        fullWidth={true}>
                    <DialogTitle id="form-dialog-title" disableTypography={true} >
                        {this.state.jmakers.length === 1 &&
                            <Avatar aria-label="Recipe" style={{backgroundColor: '#39cfb4', float: 'right'}}>
                                {this.state.jmakers[0].name.substr(0, 1) + this.state.jmakers[0].name.substr(this.state.jmakers[0].name.indexOf(' ') + 1, 1)}
                            </Avatar>
                        }
                        <p style={{color: '#39cfb4', fontSize: '22px'}}>{title}</p>
                        {jmakersFr.length === 1 &&
                            jmakersFr.map(function (item,i) {
                                        return <Chip key={i} label={item.name.substr(item.name.indexOf('-') + 1)}/>
                                    })
                        }
                        {jmakersFr.length > 1 ? <p className={classes.subtitle}>{t('collabFr')}</p> : " "}

                        {jmakersFr.length > 1 &&
                            jmakersFr.map((item,i) => (
                                  <Chip id={item.uuid}
                                        key={i}
                                        label={item.name.substr(item.name.indexOf('-') + 1)}
                                        clickable={true}
                                        onDelete={() => this.handleDelete(item, i)}
                                        style={{marginLeft: '1%', marginTop: '1%',fontSize:'11px'}}
                                  />
                                ))
                        }
                    </DialogTitle>
                    <Mutation mutation={SEND_RELANCE} onCompleted={this.onConfirm.bind(this)} onError={this.onError.bind(this)}>
                        {(sendRelance) => (
                            <Form
                                onSubmit={values => {
                                    if (values.saving === false) {
                                        sendRelance({
                                            variables: {
                                                uuid: jmakersUuid,
                                                objectFr: values.objectFr,
                                                messageFr: values.messageFr,
                                                objectEn: values.objectEn,
                                                messageEn: values.messageEn,
                                            }
                                        })
                                    }else if (values.restore) {
                                        this.resetField(values);
                                    }else if (values.saving) {
                                        this.handleClickSave(values);
                                    }
                                 }
                                }
                                initialValues={initialFormData}
                                render={({handleSubmit,form}) => (
                                    <div>
                                        <form onSubmit={handleSubmit}>
                                            {jmakersFr.length > 0 &&
                                                <DialogContent className={classes.relanceObject}>
                                                    <label className={classes.label}>
                                                        {t('object')}
                                                        <Field
                                                            name="objectFr"
                                                            type="text"
                                                            className={'form-control'}
                                                            component="input"
                                                            onKeyUp={this.handleKeyUp.bind(this)}
                                                            initialValue={initialFormData.objectFr ? initialFormData.objectFr : initialFormData.objectEn}
                                                            style={{fontSize: '13px'}}
                                                        >
                                                        </Field>
                                                    </label>
                                                </DialogContent>
                                            }
                                            {this.state.visibilitySaveObjFr &&
                                            <Tooltip title={t('save')}>
                                                <Button className={classes.buttonObj}
                                                        style={{backgroundColor: 'transparent'}}
                                                        type="submit"
                                                        onClick={() => {
                                                            form.change("saving", {'saveObjFr':true});
                                                        }}>
                                                    <SaveIcon/>
                                                </Button>
                                            </Tooltip>
                                            }
                                            {this.state.visibilityRestoreObjFr &&
                                            <Tooltip title={t('restore')}>
                                                <Button className={classes.buttonObj}
                                                        style={{backgroundColor: 'transparent'}}
                                                        type="submit"
                                                        onClick={() => {
                                                            form.change("restore", {'restoreObjFr':true});
                                                        }}>
                                                    <RestoreIcon/>
                                                </Button>
                                            </Tooltip>
                                            }
                                            {jmakersFr.length > 0 &&
                                                <DialogContent className={classes.relanceObject}>
                                                    <label className={classes.label}>
                                                        Message
                                                        <Field
                                                            name="messageFr"
                                                            component="textarea"
                                                            type="text"
                                                            className={'form-control'}
                                                            onKeyUp={this.handleKeyUp.bind(this)}
                                                            initialValue={initialFormData.messageFr}
                                                            style={{fontSize: '13px'}}
                                                        />
                                                    </label>
                                                </DialogContent>
                                            }
                                            {this.state.visibilitySaveMsgFr &&
                                            <Tooltip title={t('save')}>
                                                <Button className={classes.buttonObj}
                                                        style={{backgroundColor: 'transparent'}}
                                                        type="submit"
                                                        onClick={() => {
                                                            form.change("saving", {'saveMsgFr':true});
                                                        }}>
                                                    <SaveIcon/>
                                                </Button>
                                            </Tooltip>
                                            }
                                            {this.state.visibilityRestoreMsgFr &&
                                            <Tooltip title={t('restore')}>
                                                <Button className={classes.buttonObj}
                                                        style={{backgroundColor: 'transparent'}}
                                                        type="submit"
                                                        onClick={() => {
                                                            form.change("restore", {'restoreMsgFr':true});
                                                        }}>
                                                    <RestoreIcon/>
                                                </Button>
                                            </Tooltip>
                                            }
                                            {jmakersEn.length > 0 &&
                                                <DialogTitle id="form-dialog-title" disableTypography={true} style={{marginTop:'-3%'}}>
                                                    {jmakersEn.length === 1 &&
                                                        jmakersEn.map(function (item,i) {
                                                            return <Chip key={i} label={item.name.substr(item.name.indexOf('-') + 1)}/>
                                                    })
                                                    }
                                                    {jmakersEn.length > 1 ? <p className={classes.subtitle}>{t('collabEn')}</p> : " "}
                                                    {jmakersEn.length > 1 &&
                                                        jmakersEn.map((item, i) => (
                                                            <Chip id={item.uuid}
                                                                  key={i}
                                                                  label={item.name.substr(item.name.indexOf('-') + 1)}
                                                                  clickable={true}
                                                                  onDelete={() => this.handleDelete(item, i)}
                                                                  style={{marginLeft: '1%', marginTop: '1%',fontSize:'11px'}}/>
                                                        ))
                                                    }
                                                </DialogTitle>
                                            }
                                            {jmakersEn.length > 0 &&
                                            <DialogContent className={classes.relanceObject}>
                                                <label className={classes.label}>
                                                    {t('object')}
                                                    <Field
                                                        name="objectEn"
                                                        type="text"
                                                        className={'form-control'}
                                                        component="input"
                                                        onKeyUp={this.handleKeyUp.bind(this)}
                                                        initialValue={initialFormData.objectEn}
                                                        style={{fontSize: '13px'}}
                                                    >
                                                    </Field>
                                                </label>
                                            </DialogContent>
                                            }
                                            {this.state.visibilitySaveObjEn &&
                                            <Tooltip title={t('save')}>
                                                <Button className={classes.buttonObj}
                                                        style={{backgroundColor: 'transparent'}}
                                                        type="submit"
                                                        onClick={() => {
                                                            form.change("saving", {'saveObjEn':true});
                                                        }}>
                                                    <SaveIcon/>
                                                </Button>
                                            </Tooltip>
                                            }
                                            {this.state.visibilityRestoreObjEn &&
                                            <Tooltip title={t('restore')}>
                                                <Button className={classes.buttonObj}
                                                        style={{backgroundColor: 'transparent'}}
                                                        type="submit"
                                                        onClick={() => {
                                                            form.change("restore", {'restoreObjEn':true});
                                                        }}>
                                                    <RestoreIcon/>
                                                </Button>
                                            </Tooltip>
                                            }
                                            {jmakersEn.length > 0 &&
                                            <DialogContent className={classes.relanceObject}>
                                                <label className={classes.label}>
                                                    Message
                                                    <Field
                                                        name="messageEn"
                                                        component="textarea"
                                                        type="text"
                                                        className={'form-control'}
                                                        onKeyUp={this.handleKeyUp.bind(this)}
                                                        initialValue={initialFormData.messageEn}
                                                        style={{fontSize: '13px'}}
                                                    />
                                                </label>
                                            </DialogContent>
                                            }
                                            {this.state.visibilitySaveMsgEn &&
                                            <Tooltip title={t('save')}>
                                                <Button className={classes.buttonObj}
                                                        style={{backgroundColor: 'transparent'}}
                                                        type="submit"
                                                        onClick={() => {
                                                            form.change("saving", {'saveMsgEn':true});
                                                        }}>
                                                    <SaveIcon/>
                                                </Button>
                                            </Tooltip>
                                            }
                                            {this.state.visibilityRestoreMsgEn &&
                                            <Tooltip title={t('restore')}>
                                                <Button className={classes.buttonObj}
                                                        style={{backgroundColor: 'transparent'}}
                                                        type="submit"
                                                        onClick={() => {
                                                            form.change("restore", {'restoreMsgEn':true});
                                                        }}>
                                                    <RestoreIcon/>
                                                </Button>
                                            </Tooltip>
                                            }
                                            <DialogActions>
                                                <Button onClick={this.reInitialiseStates.bind(this)}
                                                >{t('cancel')}</Button>
                                                <Button type={"submit"}
                                                        onClick={() => {
                                                            form.change("saving", false);
                                                        }}
                                                >
                                                    {t('send')}
                                                </Button>
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
export default withSnackbar(withStyles(styles)(RelanceComponent));
