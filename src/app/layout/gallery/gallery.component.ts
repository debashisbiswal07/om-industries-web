import { Component, OnInit } from '@angular/core';
import { routerTransition } from '../../router.animations';
import { ModalDismissReasons, NgbModal } from '@ng-bootstrap/ng-bootstrap';

@Component({
  selector: 'app-gallery',
  templateUrl: './gallery.component.html',
  styleUrls: ['./gallery.component.scss'],
  animations: [routerTransition()]
})

export class GalleryComponent implements OnInit {
  public images: any = [
    { imgId: 1, imgSrc: "assets/images/transformer-images/1-250x250.png" },
    { imgId: 2, imgSrc: "assets/images/transformer-images/2-250x250.png" },
    { imgId: 3, imgSrc: "assets/images/transformer-images/3-250x250.png" },
    { imgId: 4, imgSrc: "assets/images/transformer-images/4-250x250.png" },
    { imgId: 5, imgSrc: "assets/images/transformer-images/5-250x250.png" },
    { imgId: 6, imgSrc: "assets/images/transformer-images/11-250x250.png" },
    { imgId: 7, imgSrc: "assets/images/transformer-images/12-250x250.png" },
    { imgId: 8, imgSrc: "assets/images/transformer-images/18-250x250.png" },
    { imgId: 9, imgSrc: "assets/images/transformer-images/10-250x250.png" },
    { imgId: 10, imgSrc: "assets/images/transformer-images/6-250x250.png" },
    { imgId: 11, imgSrc: "assets/images/transformer-images/7-250x250.png" },
    { imgId: 12, imgSrc: "assets/images/transformer-images/8-250x250.png" },
    { imgId: 13, imgSrc: "assets/images/transformer-images/13-250x250.png" },
    { imgId: 14, imgSrc: "assets/images/transformer-images/14-250x250.png" },
    { imgId: 15, imgSrc: "assets/images/transformer-images/15-250x250.png" },
    { imgId: 16, imgSrc: "assets/images/transformer-images/19-250x250.png" },
    { imgId: 17, imgSrc: "assets/images/transformer-images/17-250x250.png" },
    { imgId: 18, imgSrc: "assets/images/transformer-images/16-250x250.png" },
    { imgId: 19, imgSrc: "assets/images/transformer-images/20-250x250.png" },
    { imgId: 20, imgSrc: "assets/images/transformer-images/9-250x250.png" },

  ];
  selectedImage: string = "";
  closeResult: string;
  constructor(private modalService: NgbModal) { }

  ngOnInit() {
  }

  open(content, img) {
    this.selectedImage = img;
    this.modalService.open(content, { size: 'lg', backdrop: 'static' }).result.then(
      (result) => {
        this.closeResult = `Closed with: ${result}`;
      },
      (reason) => {
        this.closeResult = `Dismissed ${this.getDismissReason(reason)}`;
      }
    );
  }

  private getDismissReason(reason: any): string {
    if (reason === ModalDismissReasons.ESC) {
        return 'by pressing ESC';
    } else if (reason === ModalDismissReasons.BACKDROP_CLICK) {
        return 'by clicking on a backdrop';
    } else {
        return `with: ${reason}`;
    }
}
}
