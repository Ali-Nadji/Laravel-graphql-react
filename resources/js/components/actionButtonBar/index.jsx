import React from 'react';
import PropTypes from 'prop-types';
import SendIcon from '@material-ui/icons/Email';
import IconButton from '@material-ui/core/IconButton';
import Checkbox from '@material-ui/core/Checkbox';
import CalIcon from '@material-ui/icons/Today';
import Tooltip from '@material-ui/core/Tooltip';
import RelanceDialog from "../relanceComponent";
import styles from './styles'
import withStyles from '@material-ui/core/styles/withStyles'
import MeetingDialog from "../MeetingComponent";
import ArrowBack from '@material-ui/icons/ArrowBack';

const ActionButtonBar = (props) => {
    var relanceVisibility = "";
    var meetingVisibility = "";
    const [openRelance,setOpenRelance] = React.useState(false);
    const [openMeeting,setOpenMeeting] = React.useState(false);
    let {classes} = props
    switch(true){
        case props.rowCheckedCount === 0 :
            relanceVisibility = "hidden";
            meetingVisibility = "hidden";
            break;
        case props.rowCheckedCount === 1 :
            relanceVisibility = "visible";
            meetingVisibility = "visible";
            break;
        case props.rowCheckedCount > 1 :
            relanceVisibility = "visible";
            meetingVisibility = "hidden";
            break;
    }

    const handleClickRelance = () => {
        setOpenRelance(true);
    }
    const handleCloseRelance = () => {
        setOpenRelance(false);
        props.deselectAll();
    }

    const handleClickMeeting = () => {
        setOpenMeeting(true);
    }
    const handleCloseMeeting = () => {
        setOpenMeeting(false);
        props.deselectAll();
    }

    var title = "";
    var t = props.translate

    if(props.jmakers[0]){
        title = null === props.jmakers[0].meeting_date  ? t('set meeting date') : t('change meeting date');
    }
        return(
            <div className={props.backButton ? classes.actionBarDetail : classes.actionBarMain }>
                {props.backButton &&
                <Tooltip title={t('back')}>
                    <IconButton
                        aria-label="back"
                        onClick={props.onBackArrowClicked}>
                        <ArrowBack />
                    </IconButton>
                </Tooltip>
                }
                {props.checkAll &&
                <Tooltip title={t('all')} style={{marginLeft: '1%'}}>
                    <Checkbox
                        inputProps={{
                            'aria-label': 'checkbox with default color',
                        }}
                        onChange={props.selectAll}
                    />
                </Tooltip>
                }
                <Tooltip title={t('remind')}>
                    <IconButton
                        aria-label={t('send')}
                        style={{visibility: relanceVisibility}}
                        onClick={handleClickRelance}>
                        <SendIcon/>
                    </IconButton>
                </Tooltip>
                <Tooltip title={title}>
                    <IconButton
                        aria-label="meeting"
                        style={{visibility: meetingVisibility}}
                        onClick={handleClickMeeting}>
                        <CalIcon/>
                    </IconButton>
                </Tooltip>
                <RelanceDialog open={openRelance}
                               handleClose={handleCloseRelance}
                               jmakers={props.jmakers}
                               onUpdateJmaker={props.onUpdateJmaker}
                               translate={t}/>
                <MeetingDialog open={openMeeting}
                               handleClose={handleCloseMeeting}
                               jmakers={props.jmakers}
                               onUpdateJmaker={props.onUpdateJmaker}
                               translate={t}/>
            </div>
        )

};

ActionButtonBar.propTypes = {
    rowCheckedCount: PropTypes.number.isRequired,
};

export default  withStyles(styles)(ActionButtonBar);