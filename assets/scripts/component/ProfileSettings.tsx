import React = require('react');
import styled from 'styled-components';
import { ReactElement, useEffect, useState, useRef, MutableRefObject } from 'react';
import Loader from "./Loader";
import HorizontalLoader from "./HorizontalLoader";
import OkDialog from "./OkDialog";

const StyledProfileSettings = styled.form`
  section {
    border: 1px solid #ccc;
    border-radius: 6px;
    background: #eee;
    padding: 20px;
    margin-bottom: 40px;

    input, textarea {
      padding: 6px;
      font-size: 1em;
      border-radius: 4px;
    }

    input:invalid {
      background: #f3d1d1;
    }

    #pic {
      padding: 0;
      margin-bottom: 6px;
    }

    label {
      font-size: 1.2em;
      color: #2d2d2d;
    }

    label:after {
      content: "";
    }
  }

  #picSection {
    display: flex;

    div {
      flex: 50%;

      img {
        align-self: center; /* This will prevent the image from scaling up */
        max-width: 100%;
        max-height: 100%;
      }
    }
  }

  .url {
    span {
      display: inline-block;
      margin-right: 4px;
      font-size: 1.2em;
      color: #717171;
    }

    code {
      background: #ffffff;
      padding: 4px;
      border-radius: 4px;
    }

    .container {
      display: flex;
      align-items: center;
      height: 50px;

      input {
        display: inline-block;
        width: 55%;
        margin-bottom: 0;
      }

      .valid {
        font-size: 2em;
        color: #519a3e;
      }

      .invalid {
        font-size: 2em;
        color: #9f2f2f;
      }
    }
  }
`;

interface ProfileSettingsData {
    username: string,
    displayName: string,
    website: string,
    hash: string, // This does not exist directly in the PHP profile object. It's merged into the json response when acquiring the data.
    pic: string,
    deletePic: boolean,
    description: string,
    private: boolean,
    hideName: boolean,
}

const save = (
    picSelect: React.MutableRefObject<any>,
    data: ProfileSettingsData,
    setProfileData: React.Dispatch<React.SetStateAction<ProfileSettingsData>>,
    setShowOkLoader: React.Dispatch<React.SetStateAction<boolean>>,
    setOkMessage: React.Dispatch<React.SetStateAction<string>>
) => {
    const pics = picSelect.current.files;
    const formData = new FormData();
    if(pics.length) {
        if(pics[0].size > 1024 * 500) {
            setOkMessage('Error saving profile. Please select a picture 500KB or less.');
            setShowOkLoader(false);
            return;
        } else {
            formData.append('file', pics[0], pics[0].name);
        }
    }
    formData.append('deletePic', `${data.deletePic}`);
    formData.append('username', data.username);
    formData.append('displayName', data.displayName);
    formData.append('website', data.website);
    formData.append('description', data.description);
    formData.append('private', `${data.private}`);
    formData.append('hideName', `${data.hideName}`);

    fetch('/settings/profile', {
        method: 'POST',
        body: formData,
    })
        .then(response => {
            if (!response.ok) {
                throw response;
            }
            return response.json();
        })
        .then(data => {
            setProfileData(data);
            picSelect.current.value = null;
            setOkMessage('Profile saved.');
        })
        .catch(() => {
            setOkMessage('Error saving profile.');
        })
        .finally(() => setShowOkLoader(false));
}

const validateUsername = (
    value: string,
    setShowValidationLoader: React.Dispatch<React.SetStateAction<boolean>>,
    setValidUsername: React.Dispatch<React.SetStateAction<boolean>>,
) => {
    const exp = /^[A-Za-z0-9\-_~.]{0,50}$/;
    if(!exp.test(value)) {
        setValidUsername(false);
        return;
    }

    setShowValidationLoader(true);

    fetch('/settings/validateUsername', {
        method: 'POST',
        body: JSON.stringify({username: value}),
    })
    .then(response => {
        if (!response.ok) {
            throw response;
        }
        return response.json();
    })
    .then(data => {
        setValidUsername(data.valid);
        setShowValidationLoader(false);
    })
    .catch(() => {
        setValidUsername(false);
        setShowValidationLoader(false);
    });
}

const debounce = (func: Function, timeout: number) => {
    let timer;
    return (...args) => {
        clearTimeout(timer);
        timer = setTimeout(
            () => func.apply(this, args),
            timeout
        );
    }
}

const preview = (description: string) => {
    const formData = new FormData();
    formData.append('md', description);
    fetch('/settings/profile/preview', {
        method: 'POST',
        body: formData,
    })
    .then(response => {
        if (!response.ok) {
            throw response;
        }
        return response.text();
    })
    .then(body => {
        console.log(body);
    });
}

export default function ProfileSettings(): ReactElement {
    const [profileData, setProfileData] = useState({} as ProfileSettingsData);
    const [showPageLoader, setShowPageLoader] = useState(true);
    const [showValidationLoader, setShowValidationLoader] = useState(false);
    const [message, setMessage] = useState(null);
    const [showOkLoader, setShowOkLoader] = useState(true);
    const [showOkDialog, setShowOkDialog] = useState(false);
    const [validUsername, setValidUsername] = useState(true);
    const [okMessage, setOkMessage] = useState('');
    const picSelect = useRef(null);

    useEffect(() => {
        fetch('/settings/profile/data')
            .then(response => response.json())
            .then((data) => {
                setProfileData(data);
                setShowPageLoader(false);
            })
            .catch(() => setMessage('Error getting profile data.'))
            .finally(() => setShowPageLoader(false));
    }, []);

    return (
        <StyledProfileSettings>
            {showPageLoader && <Loader />}
            {showOkDialog &&
                <OkDialog isOpen={true}
                          showLoader={showOkLoader}
                          message={okMessage}
                          onOk={() => setShowOkDialog(false)}/>}
            {!showPageLoader && <>
                <h1>Edit Profile</h1>

                <section className="url">
                    <label htmlFor="stub">Username / URL</label>
                    <p>Valid characters are limited to: <code>A-Z</code>, <code>a-z</code>, <code>0-9</code>, <code>-</code>, <code>.</code>, <code>_</code>, <code>~</code></p>
                    <div className="container">
                        <span>https://petzshowcompanion.com/@</span>
                        <input name="stub" id="stub"
                            defaultValue={profileData.username}
                            pattern="[A-Za-z0-9\-_~\.]{0,50}"
                            onChange={
                                debounce((e) => {
                                    if(e.target.value.length == 0) {
                                        setValidUsername(null);
                                        return;
                                    }
                                    validateUsername(e.target.value, setShowValidationLoader, setValidUsername);
                                    setProfileData({...profileData, username: e.target.value});
                                }, 1000)
                            }
                        />
                        { showValidationLoader && <HorizontalLoader small={true} /> }
                        { !showValidationLoader && validUsername && <span className="material-symbols-outlined valid">sentiment_very_satisfied</span> }
                        { !showValidationLoader && validUsername != null && !validUsername && <span className="material-symbols-outlined invalid">sentiment_very_dissatisfied</span> }
                    </div>
                </section>

                <section>
                    <label htmlFor="displayName">Display Name</label>
                    <input name="displayName"
                           defaultValue={profileData.displayName}
                           onChange={(e) => setProfileData({...profileData, displayName: e.target.value})} />
                </section>

                <section>
                    <label htmlFor="website">Website</label>
                    <p>Please enter full URL including HTTP/HTTPS.</p>
                    <input name="website"
                           placeholder="https://mysite.com"
                           defaultValue={profileData.website}
                           onChange={(e) => setProfileData({...profileData, website: e.target.value})} />
                </section>

                <section>
                    <div id="picSection">
                        <div>
                            <label htmlFor="pic">Banner</label>
                            <p><input name="pic" id="pic" type="file" ref={picSelect} />
                            Banners display at a max dimension of 200 x 1000 pixels.<br/> 500KB max file size.</p>
                        </div>

                        {profileData.pic !== null && <div>
                            <img src={`/pics/${profileData.hash}/${profileData.pic}`} />
                            <br /><input type="checkbox"
                                         checked={profileData.deletePic}
                                         onChange={(e) => setProfileData({...profileData, deletePic: e.target.checked})} /> Delete
                        </div>}
                    </div>

                    <div id="hideName">
                        <hr/>
                        <p> <input type="checkbox"
                                   defaultChecked={profileData.hideName}
                                   onChange={(e) => setProfileData({...profileData, hideName: e.target.checked})} /> Hide Display Name / Username</p>
                        <p>This will prevent your Display Name or Username from showing textually at the top of your page if you wish to include it in your banner image instead.</p>
                    </div>
                </section>

                <section>
                    <label htmlFor="about">About</label>
                    <textarea name="about" id="about"
                              defaultValue={profileData.description}
                              onChange={(e) => setProfileData({...profileData, description: e.target.value})}></textarea>
                    <p>This section supports the use of <a href="https://www.markdownguide.org" target="_blank">Markdown</a>.
                    <button type="button" onClick={() => preview(profileData.description)}>Preview</button></p>
                </section>

                <section>
                    <label htmlFor="private">Make profile private?</label>
                    <input name="private" id="private" type="checkbox"
                           defaultChecked={profileData.private}
                           onChange={(e) => setProfileData({...profileData, private: e.target.checked}) } />
                    <p>This will prevent your profile from showing in the public directory of users. Alternatively, if you would like your profile to show but you want to exclude certain petz from showing, you may choose to set those petz individually to private.</p>
                </section>

                <button type="button" onClick={() => {
                    if(!validUsername) {
                        setOkMessage('Username is currently invalid. Please change it before saving.');
                        setShowOkLoader(false);
                        setShowOkDialog(true);
                    } else {
                        setShowOkLoader(true);
                        setShowOkDialog(true);
                        save(picSelect, profileData, setProfileData, setShowOkLoader, setOkMessage);
                    }
                }}>Save</button>
            </>}
        </StyledProfileSettings>
    );
}