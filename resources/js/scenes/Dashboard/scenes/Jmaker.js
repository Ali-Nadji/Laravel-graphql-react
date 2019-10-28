import React from 'react'
import { Query } from "react-apollo";
import gql from 'graphql-tag';
import Card from '@material-ui/core/Card';
import CardHeader from '@material-ui/core/CardHeader';
import CardContent from '@material-ui/core/CardContent';
import Avatar from '@material-ui/core/Avatar';
import Tooltip from '@material-ui/core/Tooltip';
import AssignmentIcon from '@material-ui/icons/Assignment';
import styles from './styles'
import withStyles from "@material-ui/core/styles/withStyles";


const Jmaker = (props) => (

    <Query query={gql`
        query jmaker($uuid: String!) 
            {
                jmaker(uuid: $uuid){
                    uuid
                    firstname
                    lastname
                    email
                    registred_at
                    prescriber {
                        language
                    }
                    runs {
                        id
                        status_rid
                        started_at
                        completed_at
                        mission { 
                            id
                            title
                        }
                    } 
              
                }
            }
    `}
           variables={{uuid:props.uuid}}
    >
        {({loading,error,data}) => {
            const jmaker = data.jmaker
            if(loading) return <p>Loading....</p>;
            if(error) return <p>{console.log(error)}</p>;
            var t = props.translate;
            let {classes} = props
            var locale = jmaker.prescriber.language === 'LANG_EN'?'en-EN':'fr-FR';
            var link = '/jmaker/rapport/'+jmaker.uuid
            return (
                <div style={{display:'flex'}}>
                    <Card style={{ width: '50%',height: '30%'}}>
                        <CardHeader
                            title={jmaker.firstname ? jmaker.firstname:""}
                            subheader={jmaker.lastname ? jmaker.lastname:""}
                            avatar={ <Avatar aria-label="Recipe" style={{backgroundColor:'#39cfb4'}}>
                                {jmaker.firstname ? jmaker.firstname.substr(0, 1):""}{jmaker.lastname ? jmaker.lastname.substr(0,1) : ""}
                            </Avatar>}
                        />
                        <CardContent style={{display:'flex',width: '110%',marginLeft: '-2%'}}>
                            <table style={{width:'45%',lineHeight:'2em'}}>
                                <tbody>
                                <tr style={{backgroundColor:'rgb(243, 243, 243)'}}>
                                    <td className={'details'}>{t('invited on')}</td>
                                    <td>{new Date(props.jmaker.created_at).toLocaleDateString(locale)}</td>
                                </tr>
                                <tr>
                                    <td className={'details'}>{t('activated on')}</td>
                                    <td>
                                        {props.jmaker.state === "JMAKER_STATE_ACTIVE" && <div>{new Date(jmaker.registred_at).toLocaleDateString(locale)}</div>}
                                    </td>
                                </tr>
                                <tr style={{backgroundColor:'rgb(243, 243, 243)'}}>
                                    <td className={'details'}>{t('interface lang')}</td>
                                    <td>
                                        {props.jmaker.language_id === "LANG_FR" && <div>{t('french')}</div>}
                                        {props.jmaker.language_id === "LANG_EN" && <div>{t('english')}</div>}
                                    </td>
                                </tr>
                                <tr>
                                    <td className={'details'}>{t('debrief on')}</td>
                                    {props.jmaker.meeting_date && <td>{new Date(props.jmaker.meeting_date).toLocaleDateString(locale)}</td>}
                                </tr>
                                </tbody>
                            </table>
                            <table style={{width:'50%',lineHeight:'2em'}}>
                                <tbody>
                                <tr style={{backgroundColor:'rgb(243, 243, 243)'}}>
                                    <td className={'details'}>{t('last connected')}</td>
                                    <td>{props.jmaker.last_page_at ? new Date(props.jmaker.last_page_at).toLocaleDateString(locale):""}</td>
                                </tr>
                                <tr>
                                    <td className={'details'}>{t('mission number')}</td>
                                    <td>{props.jmaker.missions_ct}</td>
                                </tr>
                                <tr style={{backgroundColor:'rgb(243, 243, 243)'}}>
                                    <td className={'details'}>{t('reminder sent on')}</td>
                                    <td>{props.jmaker.recall_at ? new Date(props.jmaker.recall_at).toLocaleDateString(locale) : ""}</td>
                                </tr>
                                <tr>
                                    <td className={'details'}>{t('number of reminders')}</td>
                                    <td>{props.jmaker.recall_ct}</td>
                                </tr>
                                </tbody>
                            </table>
                        </CardContent>
                    </Card>
                    <Card style={{ width: '50%',marginLeft:'2%'}}>
                        {props.jmaker.synthesis_shared_at &&
                            <div style={{float: 'right', marginTop: '3%', marginRight: '1%'}}>
                                <Tooltip title={t('synthesis')} style={{float: 'right'}}>
                                    <a href={link} target="blank">
                                        <img src={'/images/svg/jobmakerFile.svg'} width={30} height={30}></img>
                                    </a>
                                </Tooltip>
                            </div>
                        }
                        <CardHeader
                            title={t('missions')}
                            avatar={  <Avatar style={{color:'#fff',backgroundColor:'green'}}>
                                <AssignmentIcon />
                            </Avatar>}
                        />
                        <CardContent>
                            {props.jmaker.state === "JMAKER_STATE_ACTIVE" &&
                            <table style={{width: '104%', lineHeight: '2em', marginLeft: '-2%'}}>
                                <thead style={{backgroundColor:'rgb(243, 243, 243)'}}>
                                <tr>
                                    <th>{t('title')}</th>
                                    <th>{t('status')}</th>
                                    <th>{t('started at')}</th>
                                    <th>{t('finished at')}</th>
                                </tr>
                                </thead>
                                <tbody>
                                {jmaker.runs.map(function (item) {
                                    return (
                                        <tr key={item.id}>
                                            <td>{item.mission.title}</td>
                                            <td>
                                                {item.status_rid === "RUN_STATUS_ACCESSIBLE" && t('accessible')}
                                                {item.status_rid === "RUN_STATUS_VISIBLE" && t('visible')}
                                                {item.status_rid === "RUN_STATUS_FINISHED" && t('finished')}
                                                {item.status_rid === "RUN_STATUS_INPROGRESS" && t('in progress')}
                                            </td>
                                            <td>
                                                {item.status_rid === "RUN_STATUS_ACCESSIBLE" && "--"}
                                                {item.status_rid === "RUN_STATUS_VISIBLE" && "--"}
                                                {item.status_rid === "RUN_STATUS_FINISHED" && new Date(item.started_at).toLocaleDateString(locale)}
                                                {item.status_rid === "RUN_STATUS_INPROGRESS" && new Date(item.started_at).toLocaleDateString(locale)}
                                            </td>
                                            <td>
                                                {item.status_rid === "RUN_STATUS_ACCESSIBLE" && "--"}
                                                {item.status_rid === "RUN_STATUS_VISIBLE" && "--"}
                                                {item.status_rid === "RUN_STATUS_INPROGRESS" && "--"}
                                                {item.status_rid === "RUN_STATUS_FINISHED" && new Date(item.completed_at).toLocaleDateString(locale)}
                                            </td>
                                        </tr>)
                                })
                                }
                                </tbody>
                            </table>
                            }
                            <div className={classes.message}>
                                {jmaker.state === "JMAKER_STATE_ONBOARDING" && <p>{t('jmaker is onboarding')}</p>}
                                {jmaker.state === "JMAKER_STATE_INVITED" && <p>{t('jmaker not activated')}</p>}
                            </div>
                        </CardContent>
                    </Card>
                </div>
            );
        }}
    </Query>
);

export default withStyles(styles)(Jmaker);