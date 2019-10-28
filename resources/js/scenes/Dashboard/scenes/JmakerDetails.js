import React from 'react';
import Jmaker from './Jmaker';
import ActionButtonBar from "../../../components/actionButtonBar";

const JmakerDetails = (props) => {
    var t = props.translate;
    return(
        <div style={{marginTop:'1%'}}>
            <div style={{display:'flex'}}>
                    <ActionButtonBar rowCheckedCount={1}
                        jmakers={[props.jmaker]}
                        backButton={true}
                        onBackArrowClicked={props.onBackArrowClicked}
                        onUpdateJmaker={props.onUpdateJmaker}
                        translate={t}
                    />
            </div>
            <Jmaker uuid={props.jmaker.uuid}
                    jmaker={props.jmaker}
                    translate={t}
                    onUpdateJmaker={props.onUpdateJmaker}/>
        </div>
    )
};

export default JmakerDetails;